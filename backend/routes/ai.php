<?php

use App\Mcp\Servers\ClubSaasServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::local('club-saas', ClubSaasServer::class);
