<?php namespace App;

class DomainApplication extends \Illuminate\Foundation\Application {
    public function langPath() {
        return __DIR__.'/../../../../../app/lang';
    }
}