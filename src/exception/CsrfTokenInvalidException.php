<?php
namespace kuiper\web\exception;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class CsrfTokenInvalidException extends HttpException
{
}
