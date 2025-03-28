<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Show a user.
     */
    public function show(Request $request)
    {
        $user = $request->user();
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
        ];
    }

    /**
     * Update a user.
     */
    public function update(Request $request)
    {
        $messages = [
            'name.required' => 'O Nome é obrigatório!',
            'name.max' => 'O Nome deve ter menos de 255 caracteres!',
            'email.required' => 'O Email é obrigatório!',
            'email.max' => 'O Email deve ter menos de 255 caracteres!',
            'email.email' => 'O Email deve ser válido!',
            'email.unique' => 'Este Email já existe!',
        ];

        if ($request->user()->email !== $request->email) {
            $fields = $request->validate([
                'name' => 'required|max:255',
                'email' => 'required|max:255|email|unique:users',
            ], $messages);
        } else {
            $fields = $request->validate([
                'name' => 'required|max:255',
            ], $messages);
        }

        $user = $request->user();
        $user->update($fields);

        return [
            'message' => 'Dados atualizados!',
        ];
    }

    /**
     * Update a user avatar.
     */
    public function updateAvatar(Request $request)
    {
        $mime = $request->file->getMimeType();
        if (strcmp($mime, 'image/png') !== 0 && strcmp($mime, 'image/jpeg') !== 0 && strcmp($mime, 'image/svg+xml') !== 0) {
            return response([
                'errors' => [
                    'file' => ['O Avatar de perfil deve ser em PNG, JPEG ou SVG!']
                ]
            ], 422)->header('Content-Type', 'application/json');
        }

        $messages = [
            'file.required' => 'O Avatar de perfil é obrigatório!',
            'file.file' => 'O Avatar de perfil deve ser um arquivo válido!',
            'file.max' => 'O Avatar de perfil deve ter menos de 2MB!',
        ];

        $request->validate([
            'file' => 'required|file|max:2048',
        ], $messages);


        // http://localhost:8000/storage/uploads/{filename}
        $request->file('file')->storePublicly('uploads', 'public');
        $file_name = $request->file('file')->hashName();

        Storage::disk('public')->delete('uploads/' . $request->user()->avatar);

        $request->user()->update(["avatar" => $file_name]);

        return [
            'message' => 'Avatar de perfil atualizado!',
        ];
    }

    /**
     * Update a user password.
     */
    public function updatePassword(Request $request)
    {

        if (!Hash::check($request->old_password, $request->user()->password)) {
            return response([
                'errors' => [
                    'old_password' => ['As credenciais estão incorretas!']
                ]
            ], 422)->header('Content-Type', 'application/json');
        }

        $messages = [
            'password.required' => 'A Senha é obrigatória!',
            'password.confirmed' => 'A Senha e Confirmação de Senha devem ser iguais!',
        ];

        $fields = $request->validate([
            'password' => 'required|confirmed',
        ], $messages);

        $user = $request->user();
        $user->update($fields);

        return [
            'message' => 'Senha atualizada!',
        ];
    }
}
