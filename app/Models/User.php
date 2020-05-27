<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Auth;

class User extends Authenticatable implements MustVerifyEmailContract
{
    use MustVerifyEmailTrait;

    use Notifiable {
      notify as protected laravelNotify;
  }

  public function notify($instance)
  {
      // 如果要通知的人是当前用户，就不必通知了！
      if ($this->id == Auth::id()) {
          return;
      }

      // 只有数据库类型通知才需提醒，直接发送 Email 或者其他的都 Pass
      if (method_exists($instance, 'toDatabase')) {
          $this->increment('notification_count');
      }

      $this->laravelNotify($instance);
  }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'introduction', 'avatar'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function register(Request $request){
      //检查用户提交的数据是否有误
      $this->validator($request->all())->validate();

      //创建用户同时出发用户注册成功的事件， 并将用户传参
      event(new Registered($user = $this->create($request->all())));

      //登录用户
      $this->guard()->login($user);

      //调用钩子方法，`registered()`
      return $this->registered($request, $user)?: redirect($this->redirectPath());

    }

    //一个用户对应多个话题
    public function topics(){
      return $this->hasMany(Topic::class);
    }

    public function isAuthorOf($model){
      return $this->id == $model->user_id;
    }

    public function replies(){
      return $this->hasMany(Reply::class);
    }
}
