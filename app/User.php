<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username', 'password', 'role', 'features_activated',
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
        'features_activated' => 'boolean',
    ];

    /**
     * Check if user is a superuser
     *
     * @return bool
     */
    public function isSuperuser()
    {
        return $this->role === 'superuser';
    }

    /**
     * Check if user has activated features
     *
     * @return bool
     */
    public function hasActivatedFeatures()
    {
        return $this->isSuperuser() || $this->features_activated;
    }

    /**
     * Activate features for this user
     *
     * @return bool
     */
    public function activateFeatures()
    {
        if ($this->isSuperuser()) {
            return true; // Superusers always have features
        }

        $this->features_activated = true;
        return $this->save();
    }

    /**
     * Set password attribute with bcrypt hashing
     *
     * @param string $value
     * @return void
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    /**
     * Override getAuthPassword to use username field
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'username';
    }
}
