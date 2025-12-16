<?php

class AuthMiddleware
{
    public function handle()
    {
        $token = Request::bearerToken();
        
        if (!$token) {
            Response::unauthorized('Token not provided');
            return false;
        }
        
        $user = Auth::validateToken($token);
        
        if (!$user) {
            Response::unauthorized('Invalid or expired token');
            return false;
        }
        
        Auth::setUser($user);
        return true;
    }
}

