<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RechargePackage extends Model
{
    use HasFactory;
    
 public static function getRechargPlanList($request)
{
    $authid = $request->auth_user->id;

    $authUser = User::where('id', $authid)->first();
    $authMobile = $authUser ? $authUser->mobile : null;

    // Flag to block only_once plans
    $hasUsedPromotionalChat = false;

    // 1. Check in current user's promotional chat history
    $hasUsedPromotionalChat = CallChatRequest::where('user_id', $authid)
        ->where('is_promotional', 1)
        ->whereIn('request_status', [5, 7, 8]) // finished/cancelled/missed
        ->exists();

    // 2. Check if this mobile number has any deleted accounts that used chat or recharge
    if (!$hasUsedPromotionalChat && $authMobile) {
        $deletedUserIds = User::where('mobile', $authMobile)
            ->where('is_deleted', 1)
            ->pluck('id');

        if ($deletedUserIds->isNotEmpty()) {
            // Check if any deleted user has done a promotional chat
            $hasPromotionalChatDeleted = CallChatRequest::whereIn('user_id', $deletedUserIds)
                ->where('is_promotional', 1)
                ->whereIn('request_status', [5, 7, 8])
                ->exists();

            // OR check if any deleted user has done recharge or chat
            $hasRechargedOrChatted = Checkout::whereIn('user_id', $deletedUserIds)
                    ->where('order_status', 'Completed')
                    ->where('product_type', 'recharge')
                    ->exists()
                || DB::table('call_chat_request')
                    ->whereIn('user_id', $deletedUserIds)
                    ->where('request_type', 'Chat')
                    ->whereIn('request_status', [2, 5])
                    ->exists();

            if ($hasPromotionalChatDeleted || $hasRechargedOrChatted) {
                $hasUsedPromotionalChat = true;
            }
        }
    }

    // 3. Get only_once plans already purchased by current user
    $checkOnceRecord = Checkout::join('recharge_packages', 'recharge_packages.id', '=', 'checkouts.user_kundali_request_info_id')
        ->where('checkouts.product_type', 'recharge')
        ->where('checkouts.order_status', 'Completed')
        ->where('recharge_packages.only_once', 1)
        ->where('checkouts.user_id', $authid)
        ->groupBy('recharge_packages.id')
        ->pluck('recharge_packages.id');

    // 4. Get all active recharge plans except the already-purchased only_once ones
    $walletsPack = self::leftJoin('taxes', 'taxes.id', '=', 'recharge_packages.tax_id')
        ->where('recharge_packages.status', 1)
        ->whereNotIn('recharge_packages.id', $checkOnceRecord)
        ->select('recharge_packages.*', 'taxes.tax_name', 'taxes.tax_value')
        ->orderBy('recharge_packages.level', 'ASC')
        ->get();

    $planlistdata = [];

    // 5. Filter plans based on only_once usage history
    foreach ($walletsPack as $value) {
        if ($value->only_once == 1) {
            if ($hasUsedPromotionalChat) {
                continue; // skip this plan
            }

            // Optional: extra safeguard - has this plan already been purchased?
            $hasUserPurchasedThisPlan = Checkout::where('user_id', $authid)
                ->where('user_kundali_request_info_id', $value->id)
                ->where('order_status', 'Completed')
                ->where('product_type', 'recharge')
                ->exists();

            if (!$hasUserPurchasedThisPlan) {
                $planlistdata[] = $value;
            }
        } else {
            $planlistdata[] = $value;
        }
    }

    return $planlistdata;
}


}
