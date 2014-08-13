<?php

namespace Firefly\Storage\Piggybank;
use Carbon\Carbon;


/**
 * Class EloquentLimitRepository
 *
 * @package Firefly\Storage\Limit
 */
class EloquentPiggybankRepository implements PiggybankRepositoryInterface
{


    /**
     * @return mixed
     */
    public function count()
    {
        return \Piggybank::leftJoin('accounts', 'accounts.id', '=', 'piggybanks.account_id')->where(
            'accounts.user_id', \Auth::user()->id
        )->count();
    }

    /**
     * @param $piggyBankId
     *
     * @return mixed
     */
    public function find($piggyBankId)
    {
        return \Piggybank::leftJoin('accounts', 'accounts.id', '=', 'piggybanks.account_id')->where(
            'accounts.user_id', \Auth::user()->id
        )->where('piggybanks.id', $piggyBankId)->first(['piggybanks.*']);
    }

    public function countRepeating()
    {
        return \Piggybank::leftJoin('accounts', 'accounts.id', '=', 'piggybanks.account_id')->where(
            'accounts.user_id', \Auth::user()->id
        )->where('repeats', 1)->count();
    }

    public function countNonrepeating()
    {
        return \Piggybank::leftJoin('accounts', 'accounts.id', '=', 'piggybanks.account_id')->where(
            'accounts.user_id', \Auth::user()->id
        )->where('repeats', 0)->count();

    }

    /**
     * @return mixed
     */
    public function get()
    {
        return \Piggybank::with('account')->leftJoin('accounts', 'accounts.id', '=', 'piggybanks.account_id')->where(
            'accounts.user_id', \Auth::user()->id
        )->get(['piggybanks.*']);
    }

    /**
     * @param $data
     *
     * @return \Piggybank
     */
    public function store($data)
    {
        var_dump($data);
        /** @var \Firefly\Storage\Account\AccountRepositoryInterface $accounts */
        $accounts = \App::make('Firefly\Storage\Account\AccountRepositoryInterface');
        $account = isset($data['account_id']) ? $accounts->find($data['account_id']) : null;


        $piggyBank = new \Piggybank($data);
        $piggyBank->account()->associate($account);
        $today = new Carbon;
        if ($piggyBank->validate()) {
            echo 'Valid, but some more checking!';
            if($piggyBank->targetdate < $today) {
                $piggyBank->errors()->add('targetdate','Target date cannot be in the past.');
                echo 'errrrrrr on target date';
                return $piggyBank;
            }

            // first period for reminder is AFTER target date.
            // just flash a warning
            die('Here be a check. Sorry for the kill-switch. Will continue tomorrow.');

            $piggyBank->save();
        }

        return $piggyBank;
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public function update($data)
    {
        $piggyBank = $this->find($data['id']);
        if ($piggyBank) {
            $accounts = \App::make('Firefly\Storage\Account\AccountRepositoryInterface');
            $account = $accounts->find($data['account_id']);
            // update piggybank accordingly:
            $piggyBank->name = $data['name'];
            $piggyBank->target = floatval($data['target']);
            $piggyBank->account()->associate($account);
            if ($piggyBank->validate()) {
                $piggyBank->save();
            }
        }

        return $piggyBank;
    }

    /**
     * @param \Piggybank $piggyBank
     * @param            $amount
     *
     * @return mixed|void
     */
    public function updateAmount(\Piggybank $piggyBank, $amount)
    {
        $piggyBank->amount = floatval($amount);
        if ($piggyBank->validate()) {
            $piggyBank->save();
        }

    }
}