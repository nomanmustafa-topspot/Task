<?php

namespace DTApi\Repository;

use Validator;
use Illuminate\Database\Eloquent\Model;
use DTApi\Exceptions\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\Validator as IlluminateValidator;

class BaseRepository
{

    /**
     * @var Model
     */
    protected $model;

    /**
     * @var array
     */
    protected $validationRules = [];

    /**
     * @param Model $model
     */
    public function __construct(Model $model = null)
    {
        $this->model = $model;
    }

    /**
     * @return array add for array
     */
    public function validatorAttributeNames(): array
    {
        return [];
    }

    /**
     * @return Model
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|Model[]
     */
    public function all()
    {
        return $this->model->all();
    }

    /**
     * @param integer $id
     * @return Model|null
     */
    public function find($id)
    {
        return $this->model->find($id);
    }

    public function with($array)
    {
        return $this->model->with($array);
    }

    /**
     * @param integer $id
     * @return Model
     * @throws ModelNotFoundException
     */
    public function findOrFail($id)
    {
        return $this->model->findOrFail($id);
    }

    /**
     * @param string $slug
     * @return Model
     * @throws ModelNotFoundException
     */
    public function findBySlug($slug)
    {

        return $this->model->where('slug', $slug)->first();

    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    // I'm adding newQuery modal 
    public function query()
    {
        return $this->model->newQuery();
    }

    /**
     * @param array $attributes
     * @return Model
     */
    // I define using for the model and one line to return modal attributes
    public function instance(array $attributes = []): Model
    {
        return new $this->model($attributes);
    }

    /**
     * @param int|null $perPage
     * @return mixed
     */
    public function paginate($perPage = null)
    {
        return $this->model->paginate($perPage);
    }

    public function where($key, $where)
    {
        return $this->model->where($key, $where);
    }

    /**
     * @param array $data
     * @param null $rules
     * @param array $messages
     * @param array $customAttributes
     * @return \Illuminate\Validation\Validator
     */
    // No need for check the is_null condition just simply add this 
    public function validator(array $data = [], $rules = null, array $messages = [], array $customAttributes = [])
    {
        $rules = $rules ?? $this->validationRules;
        return Validator::make($data, $rules, $messages, $customAttributes);
    }

    /**
     * @param array $data
     * @param null $rules
     * @param array $messages
     * @param array $customAttributes
     * @return bool
     * @throws ValidationException
     */
    // I Created the new validator funciton for handle Muttator
    public function validate(array $data = [], $rules = null, array $messages = [], array $customAttributes = [])
    {
        $validator = $this->validator($data, $rules, $messages, $customAttributes);
        return $this->validateWithValidator($validator);
    }
    
    /**
     * Validate using the validator instance.
     *
     * @param IlluminateValidator $validator
     * @return bool
     * @throws ValidationException
     */
    // new created this function for handle validation of set name attributes
    protected function validateWithValidator(IlluminateValidator $validator): bool
    {
        if (!empty($attributeNames = $this->validatorAttributeNames())) {
            $validator->setAttributeNames($attributeNames);
        }

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return true;
    }

    /**
     * @param array $data
     * @return Model
     */
    public function create(array $data = [])
    {
        return $this->model->create($data);
    }

    /**
     * @param integer $id
     * @param array $data
     * @return Model
     */
    public function update($id, array $data = [])
    {
        $instance = $this->findOrFail($id);
        $instance->update($data);
        return $instance;
    }

    /**
     * @param integer $id
     * @return Model
     * @throws \Exception
     */
    public function delete($id)
    {
        $model = $this->findOrFail($id);
        $model->delete();
        return $model;
    }

    /**
     * @param \Illuminate\Validation\Validator $validator
     * @return bool
     * @throws ValidationException
     */
    protected function _validate(\Illuminate\Validation\Validator $validator)
    {
        if (!empty($attributeNames = $this->validatorAttributeNames())) {
            $validator->setAttributeNames($attributeNames);
        }

        if ($validator->fails()) {
            return false;
            throw (new ValidationException)->setValidator($validator);
        }

        return true;
    }

}