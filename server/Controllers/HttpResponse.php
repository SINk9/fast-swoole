<?php

namespace Server\Controllers;
use Hyperf\Utils\Codec\Json;
/**
 * HttpResponse
 * @Author: sink
 * @Date:   2019-08-09 11:58:15
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-10 17:37:19
 */

class HttpResponse
{
    /**
     * http response
     * @var \swoole_http_response
     */
    public $response;

    /**
     * http request
     * @var \swoole_http_request
     */
    public $request;
    /**
     * @var Controller
     */
    protected $controller;

    /**
     * HttpOutput constructor.
     * @param $controller
     */
    public function __construct($controller)
    {
        $this->controller = $controller;
    }


    /**
     * *
     * @param  [type]      $data
     * @param  int|integer $code
     * @param  string      $message
     * @return [type]
     */
    public function success($data = NULL, string $message = 'success', int $code = 0)
    {
        $result = [
            'code'    => $code,
            'message' => $message,
            'data'    => $data
        ];
        return $this->json($result);
    }

    /**
     * *
     * @param  int|integer $code
     * @param  string      $message
     * @return [type]
     */
    public function error(string $message = '', int $code = -1)
    {
        $code     = ($code == 0) ? -1 : $code;
        $result   = [
            'code'    => $code,
            'message' => $message
        ];
        return $this->json($result);
    }


    /**
     * *
     * @param  [type]  $path
     * @param  integer $status
     * @param  array   $headers
     * @param  [type]  $secure
     * @return [type]
     */
    public function redirectTo($path, $status = 302, $headers = [])
    {
        return $this->setStatusHeader($status)
                    ->setHeader('Location', $path)
                    ->end('');
    }


    /**
     * *
     * @param  [type]      $result
     * @param  int|integer $statusCode
     * @param  [type]      $options
     * @return [type]
     */
    public function json($result, int $statusCode = 200){
        $data = $this->toJson($result);
        return $this->setStatusHeader($statusCode)
                    ->setHeader('content-type', 'application/json; charset=utf-8')
                    ->end($data);
    }

    /**
     * @param array|\Hyperf\Utils\Contracts\Arrayable|\Hyperf\Utils\Contracts\Jsonable $data
     * @param int                                                                      $options
     *
     * @return string
     */
    protected function toJson($data, $options = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : string
    {
        try {
            $result = Json::encode($data, $options);
        } catch (\Throwable $exception) {
            throw new EncodingException($exception->getMessage(), $exception->getCode());
        }

        return $result;
    }

    /**
     * 设置
     * @param $request
     * @param $response
     */
    public function set($request, $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * 重置
     */
    public function reset()
    {
        unset($this->response);
        unset($this->request);
    }

    /**
     * Set HTTP Status Header
     *
     * @param    int    the status code
     * @param    string
     * @return HttpOutPut
     */
    public function setStatusHeader($code = 200)
    {
        if (!$this->controller->canEnd()) {
            return;
        }
        $this->response->status($code);
        return $this;
    }

    /**
     * Set Content-Type Header
     *
     * @param    string $mime_type Extension of the file we're outputting
     * @return    HttpOutPut
     */
    public function setContentType($mime_type)
    {
        if (!$this->controller->canEnd()) {
            return;
        }
        $this->setHeader('Content-Type', $mime_type);
        return $this;
    }

    /**
     * set_header
     * @param $key
     * @param $value
     * @return $this
     */
    public function setHeader($key, $value)
    {
        if (!$this->controller->canEnd()) {
            return;
        }
        $this->response->header($key, $value);
        return $this;
    }

    /**
     * 发送
     * @param string $output
     */
    public function end($output = '')
    {
        if (!$this->controller->canEnd()) {
            return;
        }
        if (is_array($output) || is_object($output)) {
            $this->setHeader('Content-Type','text/html; charset=UTF-8');
            $output = json_encode($output,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
            $output = "<pre>$output</pre>";
        }
        $this->response->end($output);
        $this->controller->endOver();
    }

    /**
     * 设置HTTP响应的cookie信息。此方法参数与PHP的setcookie完全一致。
     * @param string $key
     * @param string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httponly
     */
    public function setCookie(string $key, string $value = '', int $expire = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httponly = false)
    {
        if (!$this->controller->canEnd()) {
            return;
        }
        $this->response->cookie($key, $value, $expire, $path, $domain, $secure, $httponly);
    }

    /**
     * 输出文件
     * @param $root_file
     * @param $file_name
     * @return mixed
     */
    public function endFile($root_file, $file_name)
    {
        if (!$this->controller->canEnd()) {
            return null;
        }
        $result = httpEndFile($root_file . '/' . $file_name, $this->request, $this->response);
        $this->controller->endOver();
        return $result;
    }
}
