<?php

namespace App\Controllers;

use App\Models\User;

class AdminController
{
    public function users()
    {
        // Проверка админ-прав
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }

        // Получение всех пользователей
        $users = User::all();

        // Рендеринг
        $this->view('admin/users', ['users' => $users]);
        //     View::render('admin/users', [
        //     'title' => 'Все Юзвери',
        //     'csrf_token' => $_SESSION['csrf_token'] ?? '',
        //     'users' =>  => $users
        // ]);
    }
}
