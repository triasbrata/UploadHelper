<?php

namespace Bitdev\UploadHelper;

use BadMethodCallException;
use Config;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Support\MessageBag;
use Session;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Repositories\RepositorieInterface;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Http\FormRequest;

class UploadHelper 
{
	private $path;
	private $filename;
	private $size;
	private $countError;
	private $files = [];
	private $update;
	private $model;

	function __construct($size=1028) {
		$this->size = $size;
		$this->countError = 0;
	}
	public function setSize($size)
	{
		$this->size = $size;
	}
	public function setPath($path)
	{
		$this->path = $path;
	}
	public function setUpdate($value)
	{
		if(! is_bool($value))	
		throw new BadMethodCallException("Argument in method UploadHelper::setUpdate must be boolean ");	
		$this->update = $value;
	}
	public function get($field)
	{
		if(!isset($this->files[$field])) return '';
		return $this->files[$field];
	}
	public function run($fields,$model,FormRequest $r)
	{
		$this->setModel($model);
		if(!is_array($fields))
			throw new BadMethodCallException("Argument fields in method UploadHelper::run must array");
		foreach ($fields as $field) {
			$files[$field] = $r->file($field);
		}
		if($this->validate($files)){
			foreach ($files as $field => $file) {
				if(!is_null($file)){
					$name_file = $this->generateName($field,$file);
					$this->registerFile($field,$this->upload($file,$name_file));
				}else{
					$this->registerFile($field,$this->generateName($field));
				}
			}
			return true;
		}
		return false;

	}
	public function setModel($model)
	{
		if(! is_null($model) && is_object($model))
			$this->model = $model;
		else if($this->isUpdate())
			throw new BadMethodCallException("Model must be set");
		
	}
	private function registerFile($field,$nameFile)
	{

		$this->files[$field] = $nameFile;
	}
	private function isUpdate()
	{
		return $this->update;
	}
	
	private function getFilePath($name)
	{
		return "{$this->path}/$name";
	}
	private function isExist($name)
	{
		if($name !== ''){
			
			return (new Filesystem)->exists($this->getFilePath($name));
		}
		return false;
	}
	private function randName($file)
	{
		return str_random(40).".{$file->guessExtension()}";	
	}
	private function generateName($field,$file=null)
	{
		$newName = null;
		$nameModel = $this->model->{$field};
		if($nameModel == '' || is_null($nameModel)){
			if(! is_null($file)){
				$newName = $this->randName($file);
			}
		}
		else if( !is_null($file)){
			$newName = $this->isExist($nameModel) ? $nameModel : $this->randName($file);
		}else{
			$newName = $nameModel;
		}
		return $newName;
	}
	private function hasError()
	{
		$e = function (){
			$_e = $this->countError;
			$this->countError = 0;
			return $_e;
		};
		if($e() >= 1){
			return true;
		}
		return false;
	}
	private function makeError($error,$key='default')
	{
		$this->countError++;
		Session::flash('errors',Session::get('errors',new ViewErrorBag)->add($key,$error));

	}
	private function validate($files)
	{
		if(!is_array($files)) throw new BadMethodCallException("Argument files in method UploadHelper::validate must array");
		foreach ($files as $field => $file) {
			if(! is_null($file) ){
				if(!$file->isValid())
					$this->makeError("File pada Bidang isian $field tidak valid");
			}else{
				if(! $this->update){
					$this->makeError("File pada Bidang isian $field wajib di isi");	
				}
			}
		}
		return !$this->hasError();
	}
	private function upload($file,$name)
	{	
		$file->move($this->path,$name);
		return $name;
	}
}
