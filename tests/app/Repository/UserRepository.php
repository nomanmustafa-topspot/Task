<?php

namespace DTApi\Repository;
// Removed the un neccessory files of start load 
use DTApi\Models\{Company, Department, Type, UsersBlacklist, User, Town, UserMeta, UserTowns, UserLanguages};
use Monolog\Logger;
use Illuminate\Support\Facades\DB;
use Monolog\Handler\{StreamHandler, FirePHPHandler};

/**
 * Class BookingRepository
 * @package DTApi\Repository
 */
class UserRepository extends BaseRepository
{

    protected $model;
    protected $logger;

    /**
     * @param User $model
     */
    // Adjust the public constructor function and add the initializeLogger function
    public function __construct(User $model)
    {
        parent::__construct($model);
        $this->initializeLogger();
    }
    
    /**
     * Initialize the logger.
     */
    protected function initializeLogger()
    {
        $this->logger = new Logger('admin_logger');
        $this->logger->pushHandler(new StreamHandler(storage_path('logs/admin/laravel-' . date('Y-m-d') . '.log'), Logger::DEBUG));
        $this->logger->pushHandler(new FirePHPHandler());
    }
    // In this add the multiple function for update the user role wise data and meta handle user language
    // this function split into multiple function i created privated function
    public function createOrUpdate($id = null, array $request)
    {
        $user = $id ? User::findOrFail($id) : new User;

        $this->updateUserData($user, $request);
        $this->updateUserRole($user, $request['role']);
        $this->handleMetaData($user, $request);

        $this->handleBlacklist($user, $request['translator_ex'] ?? []);
        $this->handleUserLanguages($user, $request['user_language'] ?? []);
        $this->handleTowns($user, $request['user_towns_projects'] ?? [], $request['new_towns'] ?? null);

        $this->updateUserStatus($user, $request['status']);

        return $user;
    }

    /**
     * Update user basic information.
     *
     * @param User $user
     * @param array $request
     */
    protected function updateUserData(User $user, array $request)
    {
        $user->fill([
            'user_type' => $request['role'],
            'name' => $request['name'],
            'company_id' => $request['company_id'] ?: 0,
            'department_id' => $request['department_id'] ?: 0,
            'email' => $request['email'],
            'dob_or_orgid' => $request['dob_or_orgid'],
            'phone' => $request['phone'],
            'mobile' => $request['mobile'],
        ]);

        if (isset($request['password'])) {
            $user->password = bcrypt($request['password']);
        }

        $user->save();
    }

    /**
     * Update user role.
     *
     * @param User $user
     * @param int $role
     */
    protected function updateUserRole(User $user, int $role)
    {
        $user->detachAllRoles();
        $user->attachRole($role);
    }

    /**
     * Handle user meta data.
     *
     * @param User $user
     * @param array $request
     */
    protected function handleMetaData(User $user, array $request)
    {
        $userMeta = UserMeta::firstOrCreate(['user_id' => $user->id]);
        $userMeta->fill($this->getMetaData($request));
        $userMeta->save();
    }

    /**
     * Get meta data array.
     *
     * @param array $request
     * @return array
     */
    protected function getMetaData(array $request)
    {
        return [
            'consumer_type' => $request['consumer_type'] ?? null,
            'customer_type' => $request['customer_type'] ?? null,
            'username' => $request['username'] ?? null,
            'post_code' => $request['post_code'] ?? null,
            'address' => $request['address'] ?? null,
            'city' => $request['city'] ?? null,
            'town' => $request['town'] ?? null,
            'country' => $request['country'] ?? null,
            'reference' => isset($request['reference']) && $request['reference'] === 'yes' ? 1 : 0,
            'additional_info' => $request['additional_info'] ?? null,
            'cost_place' => $request['cost_place'] ?? null,
            'fee' => $request['fee'] ?? null,
            'time_to_charge' => $request['time_to_charge'] ?? null,
            'time_to_pay' => $request['time_to_pay'] ?? null,
            'charge_ob' => $request['charge_ob'] ?? null,
            'customer_id' => $request['customer_id'] ?? null,
            'charge_km' => $request['charge_km'] ?? null,
            'maximum_km' => $request['maximum_km'] ?? null,
        ];
    }

    /**
     * Handle blacklist operations for a user.
     *
     * @param User $user
     * @param array $translatorIds
     */
    protected function handleBlacklist(User $user, array $translatorIds)
    {
        if (empty($translatorIds)) {
            UsersBlacklist::where('user_id', $user->id)->delete();
            return;
        }

        $existingBlacklist = UsersBlacklist::where('user_id', $user->id)->pluck('translator_id')->toArray();
        $newBlacklist = array_diff($translatorIds, $existingBlacklist);

        foreach ($newBlacklist as $translatorId) {
            UsersBlacklist::firstOrCreate(['user_id' => $user->id, 'translator_id' => $translatorId]);
        }

        UsersBlacklist::deleteFromBlacklist($user->id, $existingBlacklist);
    }

    /**
     * Handle user language operations.
     *
     * @param User $user
     * @param array $languages
     */
    protected function handleUserLanguages(User $user, array $languages)
    {
        foreach ($languages as $langId) {
            UserLanguages::firstOrCreate(['user_id' => $user->id, 'lang_id' => $langId]);
        }

        UserLanguages::deleteLang($user->id, $languages);
    }

    /**
     * Handle towns associated with a user.
     *
     * @param User $user
     * @param array $townIds
     * @param string|null $newTown
     */
    protected function handleTowns(User $user, array $townIds, $newTown = null)
    {
        if ($newTown) {
            $town = Town::create(['townname' => $newTown]);
            $townIds[] = $town->id;
        }

        DB::table('user_towns')->where('user_id', '=', $user->id)->delete();

        foreach ($townIds as $townId) {
            UserTowns::firstOrCreate(['user_id' => $user->id, 'town_id' => $townId]);
        }
    }

    /**
     * Update user status.
     *
     * @param User $user
     * @param string $status
     */
    protected function updateUserStatus(User $user, $status)
    {
        if ($user->status !== $status) {
            $status === '1' ? $this->enable($user->id) : $this->disable($user->id);
        }
    }

    public function enable($id)
    {
        $user = User::findOrFail($id);
        $user->status = '1';
        $user->save();

    }

    public function disable($id)
    {
        $user = User::findOrFail($id);
        $user->status = '0';
        $user->save();

    }

    public function getTranslators()
    {
        return User::where('user_type', 2)->get();
    }
    
}