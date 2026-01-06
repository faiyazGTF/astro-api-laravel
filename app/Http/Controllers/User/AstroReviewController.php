<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User\ReviewsModel;
use App\Models\User\RatingModel;
use App\Http\Controllers\FireBaseActionController;
use App\Models\User\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AstroReviewController extends Controller
{

    public function getAstroReview($astroid)
    {
        $response = ReviewsModel::getAstroReview($astroid);
        return response()->json([
            'statusCode' => 200,
            'status' => true,
            'message' => 'success',
            'data' => $response
        ]);
    }

    public function Add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'expert_id'   => 'required|exists:users,id',
            'comments'    => 'required|string',
            'consult_id'  => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 403,
                'status'     => false,
                'message'    => 'Please fill all mandatory fields.',
                'errors'     => $validator->errors()
            ]);
        }

        $userId = auth()->id();
        $request->merge(['user_id' => $userId]);

        $existing = ReviewsModel::where([
            'user_id'    => $userId,
            'to_experts' => $request->expert_id,
            'consult_id' => $request->consult_id,
        ])->first();

        if ($existing) {
            $existing->comments = $request->comments;
            $existing->save();

            $getuser = User::find($userId);
            $getfcmtoken = getFcmToken($request->expert_id);

            $notificationarray = [
                'title' => 'New review',
                'message' => $existing->comments,
                'image' => image_url($getuser->image, '/public/cms-images/user-images/'),
                'type' => 'review',
                'senderid' => $getuser->id,
                'url' => 'astroera-astro://review'
            ];

            FireBaseActionController::PushNOtificationAuthdata($getfcmtoken, $notificationarray);

            return response()->json([
                'statusCode' => 200,
                'status'     => true,
                'message'    => 'Your comment has been updated.'
            ]);
        }


        $created = ReviewsModel::saveReview($request);

        if ($created) {
            $getuser = User::find($userId);
            $getfcmtoken = getFcmToken($request->expert_id);

            $notificationarray = [
                'title' => 'New review',
                'message' => $request->comments,
                'image' => image_url($getuser->image, '/public/cms-images/user-images/'),
                'type' => 'review',
                'senderid' => $getuser->id,
                'url' => 'astroera-astro://review'
            ];

            $firebaseresult =  FireBaseActionController::PushNOtificationAuthdata($getfcmtoken, $notificationarray);

            return response()->json([
                'statusCode' => 200,
                'status'     => true,
                'message'    => 'Review submitted successfully.'
            ]);
        }


        return response()->json([
            'statusCode' => 500,
            'status'     => false,
            'message'    => 'Something went wrong.'
        ]);
    }

    public function Add_rating(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'expert_id' => 'required|exists:users,id',
            'rating' => 'required|numeric|min:1|max:5',
            'consult_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 403,
                'status' => false,
                'message' => 'Please fill all mandatory fields.',
                'errors' => $validator->errors()
            ]);
        }

        $userId = auth()->id();
        $request->merge(['user_id' => $userId]);

        $existingReview = RatingModel::where('user_id', $userId)
            ->where('to_experts', $request->expert_id)
            ->where('consult_id', $request->consult_id)
            ->first();

        if ($existingReview) {
            return response()->json([
                'statusCode' => 409,
                'status' => false,
                'message' => 'You have already rated this consult history.'
            ]);
        }

        $response = RatingModel::saveRating($request);

        if ($response) {

            $getuser = User::find($userId);
            $getfcmtoken = getFcmToken($request->expert_id);

            $notificationarray = [
                'title' => 'New rating',
                'message' => 'Great job! You have new rating.',
                'image' => image_url($getuser->image, '/public/cms-images/user-images/'),
                'type' => 'review',
                'senderid' => $getuser->id,
                'url' => 'astroera-astro://review'
            ];

            FireBaseActionController::PushNOtificationAuthdata($getfcmtoken, $notificationarray);

            return response()->json([
                'statusCode' => 200,
                'status' => true,
                'message' => 'Rating submitted successfully.',
                'review_id' => $response
            ]);
        }

        return response()->json([
            'statusCode' => 500,
            'status' => false,
            'message' => 'Something went wrong.',
        ]);
    }

    public function is_anonymous(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'expert_id' => 'required|exists:users,id',
            'review_id' => 'required',
            'is_anonymous' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 403,
                'status' => false,
                'message' => 'Please fill all mandatory fields.',
                'errors' => $validator->errors()
            ]);
        }

        $userId = auth()->id();
        $request->merge(['user_id' => $userId]);


        $response = RatingModel::anonymous_status($request);

        if ($response) {
            return response()->json([
                'statusCode' => 200,
                'status' => true,
                'message' => 'Anonymous status changed successfully.'
            ]);
        }

        return response()->json([
            'statusCode' => 500,
            'status' => false,
            'message' => 'Something went wrong.',
        ]);
    }

    public function UserOnetimerate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'required|numeric|min:1|max:5', // Example range
        ]);

        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 403,
                'status' => false,
                'message' => 'Please fill all mandatory fields.',
                'errors' => $validator->errors()
            ]);
        }

        $userId = auth()->id(); // Or use $request->user()->id;
        $request->merge(['user_id' => $userId]);

        // Check if user already has a lifetime rating
        $user = User::find($userId);

        if ($user && !is_null($user->life_time_rate)) {
            return response()->json([
                'statusCode' => 409,
                'status' => false,
                'message' => 'You have already given a rating.'
            ]);
        }

        // Update the life_time_rate
        $user->life_time_rate = $request->rating;
        $saved = $user->save();

        if ($saved) {
            return response()->json([
                'statusCode' => 200,
                'status' => true,
                'message' => 'User lifetime rating submitted successfully.',
                'user_id' => $user->id
            ]);
        }

        return response()->json([
            'statusCode' => 500,
            'status' => false,
            'message' => 'Something went wrong.'
        ]);
    }


    public function getAstroRating($astroid)
    {
        $response = RatingModel::getAstroRating($astroid);
        return response()->json([
            'statusCode' => 200,
            'status' => true,
            'message' => 'success',
            'data' => $response
        ]);
    }
}
