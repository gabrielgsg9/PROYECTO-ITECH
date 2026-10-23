<?php
require_once __DIR__.'/../Repository/UserRepository.php';
/** Reglas de registro e inicio de sesión independientes del adaptador JSON/SQL. */
class UserService
{
    private UserRepository $repository;
    public function __construct(?UserRepository $repository=null){$this->repository=$repository??new UserRepository();}
    /** Crea una cuenta de paciente desde recepción usando el mismo esquema que el registro existente. */
    public function createPatient(string $name,string $email,string $password,string $confirmation):array
    {
        $name=trim(preg_replace('/\s+/u',' ',$name)??$name);$email=strtolower(trim($email));$errors=[];
        if($name===''||strlen($name)>120)$errors[]='Ingresá un nombre de hasta 120 caracteres.';
        if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>254)$errors[]='Ingresá un email válido.';
        if(strlen($password)<4)$errors[]='La contraseña debe tener al menos 4 caracteres.';
        if($password!==$confirmation)$errors[]='Las contraseñas ingresadas no coinciden.';
        if($errors)return ['user'=>null,'errors'=>$errors];
        $username=(string)strstr($email,'@',true);
        try{$user=$this->repository->create(['name'=>$name,'username'=>$username,'email'=>$email,'password_hash'=>password_hash($password,PASSWORD_DEFAULT),'role'=>'paciente']);}
        catch(DomainException $e){return ['user'=>null,'errors'=>['Ya existe una cuenta con ese email o nombre de usuario.']];}
        return ['user'=>$user,'errors'=>[]];
    }
    public function registerUser(string $email,string $password,string $confirmation):array{$email=strtolower(trim($email));$errors=[];if(!filter_var($email,FILTER_VALIDATE_EMAIL))$errors[]='Ingresá un email válido.';if(strlen($password)<4)$errors[]='La contraseña debe tener al menos 4 caracteres.';if($password!==$confirmation)$errors[]='Las contraseñas ingresadas no coinciden.';if($errors)return ['user'=>null,'errors'=>$errors];$username=(string)strstr($email,'@',true);try{$u=$this->repository->create(['name'=>$username,'username'=>$username,'email'=>$email,'password_hash'=>password_hash($password,PASSWORD_DEFAULT),'role'=>'paciente']);}catch(DomainException $e){return ['user'=>null,'errors'=>['Ya existe una cuenta con ese email o nombre de usuario.']];}return ['user'=>$u,'errors'=>[]];}
    public function loginUser(string $identifier,string $password):?array{$u=$this->repository->findByName($identifier);return $u&&isset($u['password_hash'])&&password_verify($password,$u['password_hash'])?$u:null;}
}
