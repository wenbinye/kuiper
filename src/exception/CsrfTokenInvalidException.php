<?php
namespace chaozhuo\web\exception;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class CsrfTokenInvalidException extends HttpException
{
}
