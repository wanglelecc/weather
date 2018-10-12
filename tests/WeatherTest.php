<?php
/**
 * Created by PhpStorm.
 * User: lele.wang
 * Date: 2018/10/12
 * Time: 15:58
 */

namespace Wanglelecc\Weather\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Mockery\Matcher\AnyArgs;
use PHPUnit\Framework\TestCase;
use Wanglelecc\Weather\Exceptions\HttpException;
use Wanglelecc\Weather\Exceptions\InvalidArgumentException;
use Wanglelecc\Weather\Weather;

class WeatherTest extends TestCase
{
    // 52ec17455cdd4809decba043f9578468
    protected $key = 'mock-key';
    
    public function testGetHttpClient()
    {
        $w = new Weather($this->key);
        
        $this->assertInstanceOf(ClientInterface::class, $w->getHttpClient());
    }
    
    public function testSetGuzzleOptions()
    {
        $w = new Weather($this->key);
    
        // 设置参数前，timeout 为 null
        $this->assertNull($w->getHttpClient()->getConfig('timeout'));
    
        // 设置参数
        $w->setGuzzleOptions(['timeout' => 5000]);
        
        // 设置参数后，timeout 为 5000
        $this->assertSame(5000, $w->getHttpClient()->getConfig('timeout'));
    }
    
    public function testGetWeather()
    {
        // json
        // 创建模拟接口响应值
        $response = new Response(200, [], '{"success": true}');
        
        // 创建模拟 http client.
        $client = \Mockery::mock(Client::class);
        
        // 指定将会生产的行为（在后续的测试中将会按下面的参数来调用）
        $client->allows()->get('https://restapi.amap.com/v3/weather/weatherInfo', [
            'query' => [
                'key'           => $this->key,
                'city'          => '北京',
                'output'        => 'json',
                'extenstions'   => 'base',
            ]
        ])->andReturn($response);
        
        $w = \Mockery::mock(Weather::class, [$this->key])->makePartial();
        $w->allows()->getHttpClient()->andReturn($client);
        
        $this->assertSame(['success' => true], $w->getWeather('北京'));
    
    
        // xml
        // 创建模拟接口响应值
        $response = new Response(200, [], '<hello>content</hello>');
    
        // 创建模拟 http client.
        $client = \Mockery::mock(Client::class);
    
        // 指定将会生产的行为（在后续的测试中将会按下面的参数来调用）
        $client->allows()->get('https://restapi.amap.com/v3/weather/weatherInfo', [
            'query' => [
                'key'           => $this->key,
                'city'          => '北京',
                'output'        => 'xml',
                'extenstions'   => 'all',
            ]
        ])->andReturn($response);
    
        $w = \Mockery::mock(Weather::class, [$this->key])->makePartial();
        $w->allows()->getHttpClient()->andReturn($client);
    
        $this->assertSame('<hello>content</hello>', $w->getWeather('北京', 'all', 'xml'));
    }
    
    public function testGetWeatherWithInvalidType()
    {
        $w = new Weather($this->key);
        
        // 断言会抛出异常类
        $this->expectException(InvalidArgumentException::class);
        
        // 断言异常消息为 'Invalid type value(base/all):foo'
        $this->expectExceptionMessage('Invalid type value(base/all):foo');
        
        $w->getWeather('北京', 'foo');
        
        $this->fail('Faild to assert getWeather throw exception with invalid argument.');
    }
    
    public function testGetWeatherWithInvalidFormat()
    {
        $w = new Weather($this->key);
        
        // 断言会抛出此异常
        $this->expectException(InvalidArgumentException::class);
        
        // 断言异常消息为 'Invalid response format::array'
        $this->expectExceptionMessage('Invalid response format:array');
        
        // 因为支持的格式为 xml/json,所以传入 array 抛出异常
        $w->getWeather('北京', 'base', 'array');
        
        // 如果没有抛出异常，就会运行到这，标记当前测试没成功
        $this->fail('Faild to assert getWeather throw exception with invalid argument.');
    }
    
    public function testGetWeatherWithGuzzleRuntimeException(){
        $clinet = \Mockery::mock(Client::class);
        $clinet->allows()
            ->get(new AnyArgs()) // 由于上面的用例已经验证过参数传递，所以这里就不关心参数了。
            ->andThrow(new \Exception('request timeout'));  // 当调用 get 方法时会抛出异常。
        
        $w = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->allows()->getHttpClient()->andReturn($clinet);
        
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('request timeout');
        
        $w->getWeather('北京');
    }
    
}