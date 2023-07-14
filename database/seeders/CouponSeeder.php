<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Coupon;
use Illuminate\Support\Facades\DB;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Start fresh
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Coupon::truncate();
        DB::table('account_coupon')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $seedCouponPayload = [
            'amount' => 10,
            'type_slug' => 'affiliate_code',
            'reward_type' => 'discount_percentage',
            'code' => 'seed_discount_coupon',
            'number_of_uses' => 1234,
            'reward_duration_in_months' => 12
        ];
        $coupon = Coupon::createFromPayload($seedCouponPayload);
        $coupon->save();
    }
}
