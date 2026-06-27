@echo off
echo Starting Curtains Management System...

cd /d "%~dp0backend"

echo Running migrations...
"C:\Program Files\php\php.exe" artisan migrate --force

echo Setting up stores...
"C:\Program Files\php\php.exe" artisan tinker --execute="foreach([1,2,3,4] as $i){ $name='Store '.$i; if(!App\Models\Store::find($i)){ DB::table('stores')->insert(['id'=>$i,'name'=>$name,'created_at'=>now(),'updated_at'=>now()]); echo $name.' created\n'; } else { echo $name.' OK\n'; } }"

echo Setting up admin user...
"C:\Program Files\php\php.exe" artisan tinker --execute="$u = App\Models\User::where('email','admin@curtains.com')->first(); if($u){ $u->role='admin'; $u->store_id=null; $u->save(); echo 'Admin OK'; } else { App\Models\User::create(['name'=>'Admin','email'=>'admin@curtains.com','password'=>bcrypt('admin123'),'role'=>'admin']); echo 'Admin created'; }"

echo Starting Laravel server at http://127.0.0.1:8000
"C:\Program Files\php\php.exe" artisan serve
