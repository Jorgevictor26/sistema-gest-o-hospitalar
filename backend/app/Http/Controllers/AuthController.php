<?php

namespace App\Http\Controllers;

use App\DTOs\LoginDTO;
use App\Exceptions\BlockedAccountException;
use App\Exceptions\InvalidCredentialsException;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $auth = $this->authService->login(
                LoginDTO::fromArray($request->validated())
            );

            return response()->json($auth);
        } catch (InvalidCredentialsException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 401);
        } catch (BlockedAccountException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 403);
        }
    }
}
