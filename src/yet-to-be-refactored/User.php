<?php

namespace TheRealJanJanssens\Pakka\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;
    use HasFactory;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'bio',
        'role',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /*
    |------------------------------------------------------------------------------------
    | Validations
    |------------------------------------------------------------------------------------
    */
    public static function rules($update = false, $id = null)
    {
        $commun = [
            'email' => "required|email|unique:users,email,$id",
            'password' => 'nullable|confirmed',
            'avatar' => 'image',
        ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
            'email' => 'required|email|max:191|unique:users',
            'password' => 'confirmed|min:6', //required|
        ]);
    }

    /*
    |------------------------------------------------------------------------------------
    | Attributes
    |------------------------------------------------------------------------------------
    */
    public function setPasswordAttribute($value = '')
    {
        $this->attributes['password'] = bcrypt($value);
    }

    public function getAvatarAttribute($value)
    {
        if (! $value) {
            return '';
        }

        return config('pakka.avatar.public').$value;
    }

    public function setAvatarAttribute($photo)
    {
        $this->attributes['avatar'] = move_file($photo, 'avatar');
    }

    /*
    |------------------------------------------------------------------------------------
    | Boot
    |------------------------------------------------------------------------------------
    */
    public static function boot()
    {
        parent::boot();
        static::updating(function ($user) {
            $original = $user->getOriginal();

            if (\Hash::check('', $user->password)) {
                $user->attributes['password'] = $original['password'];
            }
        });
    }

    public static function getUser($id)
    {
        $result = User::select([
        'users.id',
        'users.role',
        'users.name',
        'user_details.firstname',
        'user_details.lastname',
        'user_details.address',
        'user_details.city',
        'user_details.zip',
        'user_details.country',
        'user_details.phone',
        'users.email',
        'user_details.company_name',
        'user_details.vat',
        'user_details.birthday',
        'users.bio', ])
        ->leftJoin('user_details', 'users.id', '=', 'user_details.user_id')
        ->where('users.id', $id)
        ->first();

        return $result;
    }

    public static function getUserByEmail($email)
    {
        $result = User::select([
        'users.id',
        'users.role',
        'users.name',
        'user_details.firstname',
        'user_details.lastname',
        'user_details.address',
        'user_details.city',
        'user_details.zip',
        'user_details.country',
        'user_details.phone',
        'users.email',
        'user_details.company_name',
        'user_details.vat',
        'user_details.birthday',
        'users.bio', ])
        ->leftJoin('user_details', 'users.id', '=', 'user_details.user_id')
        ->where('users.email', $email)
        ->first();

        return $result;
    }

    /*
    |------------------------------------------------------------------------------------
    | Get Users
    |
    | $role = get users by role. When you enter 0 then it retrieves all user/admin tier users (above role 5)
    |------------------------------------------------------------------------------------
    */

    public static function getUsers($role = null)
    {
        $result = User::select([
        'users.id',
        'users.role',
        'users.name',
        'user_details.firstname',
        'user_details.lastname',
        'user_details.address',
        'user_details.city',
        'user_details.zip',
        'user_details.country',
        'user_details.phone',
        'users.email',
        'user_details.company_name',
        'user_details.vat',
        'user_details.birthday',
        'users.bio', ])
        ->leftJoin('user_details', 'users.id', '=', 'user_details.user_id');

        switch (true) {
            case $role !== null && $role !== 0:
                $result = $result->where('users.role', $role);

                break;
            case $role == 0:
                $result = $result->where('users.role', '>=', 5);

                break;
        }

        $result = $result->get();

        return $result;
    }

    public static function constructSelect($role = null)
    {
        $users = User::getUsers($role);

        if ($users) {
            foreach ($users as $user) {
                if (! empty($user['company_name'])) {
                    $result[$user['id']] = $user['company_name'];
                } else {
                    $result[$user['id']] = $user['name'];
                }
            }
        } else {
            $result = null;
        }

        //array_unshift($result, trans("app.select_option") );

        return $result;
    }
}
