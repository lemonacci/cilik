<?php

namespace Modules\User\Http\Controllers;

use App\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use GroceryCrud\Core\GroceryCrud;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    private function _getDatabaseConnection() {
        $databaseConnection = config('database.default');
        $databaseConfig = config('database.connections.' . $databaseConnection);


        return [
            'adapter' => [
                'driver' => 'Pdo_Mysql',
                'database' => $databaseConfig['database'],
                'username' => $databaseConfig['username'],
                'password' => $databaseConfig['password'],
                'charset' => 'utf8'
            ]
        ];
    }

    private function _getGroceryCrudEnterprise() {
        $database = $this->_getDatabaseConnection();
        $config = config('grocerycrud');

        $crud = new GroceryCrud($config, $database);

        return $crud;
    }

    private function _show_output($output, $title = '', $data = '') {
        if ($output->isJSONResponse) {
            return response($output->output, 200)
                  ->header('Content-Type', 'application/json')
                  ->header('charset', 'utf-8');
        }

        $css_files = $output->css_files;
        $js_files = $output->js_files;
        $output = $output->output;

        return view('grocery', [
            'output' => $output,
            'css_files' => $css_files,
            'js_files' => $js_files,
            'title' => $title,
            'data' => $data
        ]);
    }

    /**
     * Show the datagrid for customers
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $title = "Users";
        $crud = $this->_getGroceryCrudEnterprise();

        $crud->setTable('users');
        $crud->setSubject('User', 'Users');
        $crud->setSkin('bootstrap-v4');
        $crud->columns(['name', 'last_name', 'score', 'email', 'role_id']);
        $crud->requiredFields(['name', 'last_name', 'score', 'email', 'role_id']);
        $crud->setRelation('role_id', 'roles', 'name');
        $crud->displayAs([
            'role_id' => 'Role'
        ]);
        $crud->addFields(['name', 'last_name', 'score', 'email', 'password', 'role_id']);
        $crud->editFields(['name', 'last_name', 'score', 'email', 'role_id']);
        $crud->callbackAfterInsert(function ($s) {
            $data = User::find($s->insertId);
            $data->password = Hash::make($data->password);
            $data->created_at = now();
            $data->save();

            return $s;
        });
        $crud->callbackAfterUpdate(function ($s) {
            $data = User::find($s->primaryKeyValue);
            $data->updated_at = now();
            $data->save();

            return $s;
        });

        $output = $crud->render();

        return $this->_show_output($output, $title);
    }
}
