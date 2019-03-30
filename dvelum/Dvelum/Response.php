<?php
/**
 *  DVelum project https://github.com/dvelum/dvelum
 *  Copyright (C) 2011-2017  Kirill Yegorov
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);
namespace Dvelum;

use \Exception as Exception;

class Response
{
    const FORMAT_HTML = 'html';
    const FORMAT_JSON = 'json';

    protected $format = self::FORMAT_HTML;

    protected $buffer ='';
    protected $sent = false;
    /**
     * @return Response
     */
    static public function factory()
    {
        static $instance = null;

        if(empty($instance)){
            $instance = new static();
        }

        return $instance;
    }

    /**
     * Send redirect header
     * @param mixed $location
     */
    public function redirect($location) : void
    {
        \Response::redirect($location);
    }

    /**
     * Add string to response buffer
     * @param string $string
     */
    public function put(string $string) : void
    {
        if($this->sent){
            trigger_error('The response was already sent');
        }
        $this->buffer.= $string;
    }

    /**
     * Send response, finish request
     */
    public function send() : void
    {
        if($this->sent){
            throw new Exception('Response already sent');
        }

        if($this->format === self::FORMAT_JSON){
            $this->header('Content-Type: application/json');
        }

        echo $this->buffer;

        if(function_exists('fastcgi_finish_request')){
            fastcgi_finish_request();
        }
        $this->sent = true;
    }

    /**
     * Send error message
     * @param string $message
     * @param array $errors
     * @return void
     */
    public function error($message , array $errors = []) : void
    {
        @ob_clean();
        switch ($this->format){
            case self::FORMAT_JSON :
                $message = json_encode(['success'=>false,'msg'=>$message,'errors'=>$errors]);
                break;
            case self::FORMAT_HTML :
                $this->notFound();
        }
        $this->put($message);
        $this->send();
    }

    /**
     * Send success response
     * @param array $data
     * @param array $params
     * @return void
     */
    public function success(array $data = [], array $params = []) : void
    {
        $message  = '';
        switch ($this->format)
        {
            case self::FORMAT_HTML:

                if(Config::storage()->get('main.php')->get('development')){
                    $this->put('<pre>');
                    $this->put(var_export(array_merge(['data'=>$data],$params),true));
                }
                break;
            case self::FORMAT_JSON :
                $message = ['success'=>true,'data'=>$data];
                if(!empty($params)){
                    $message = array_merge($message, $params);
                }
                $message = json_encode($message);
                break;
        }
        $this->put($message);
        $this->send();
    }

    /**
     * Set response format
     * @param string $format
     * @return void
     */
    public function setFormat(string $format) : void
    {
        $this->format = $format;
    }

    /**
     * Send JSON
     * @param array $data
     * @return void
     */
    public function json(array $data = []) : void
    {
        $this->put(json_encode($data));
        $this->send();
    }

    /**
     * Send 404 Response header
     * @return void
     */
    public function notFound() : void
    {
        if(isset($_SERVER["SERVER_PROTOCOL"])){
            $this->header($_SERVER["SERVER_PROTOCOL"]."/1.0 404 Not Found");
        }
    }

    /**
     * Send response header
     * @param string $string
     * @return void
     */
    public function header(string $string) : void
    {
        \header($string);
    }

    /**
     * Is sent
     * @return bool
     */
    public function isSent() : bool
    {
        return $this->sent;
    }
}