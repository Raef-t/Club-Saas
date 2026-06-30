<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

class HelloTool extends Tool
{
    public function handle(Request $request): Response
    {
        return Response::text("hello from MCP 🚀");
    }
}
