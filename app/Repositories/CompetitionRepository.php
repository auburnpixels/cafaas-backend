<?php

namespace App\Repositories;

use App\Models\Competition;

/**
 * @class CompetitionRepository
 */
class CompetitionRepository
{
    /**
     * @return mixed
     */
    public function findBySlug(string $slug)
    {
        return Competition::where('slug', $slug)
            ->select(
                'uuid',
                'slug',
                'affiliate_commission',
                'id',
                'name',
                'type',
                'draw_at',
                'status',
                'tickets_bought',
                'available_tickets',
                'user_id',
                'question_id',
                'ending_at',
                'panel_color',
                'ticket_price',
                'listing_price',
                'shipping_price',
                'summary',
                'unique_link_amount',
                'promotional_image',
                'promotional_video',
                'body_color',
                'highlight_color',
                'text_color',
                'threshold',
                'charity_id',
                'category_id',
                'charity_donation',
                'max_tickets_per_user',
                'shipping_costs',
                'free_tickets_issued'
            )
            ->with('category:id,name,slug')
            ->with('question:id,question')
            ->with('charities:id,logo,name,website,content')
            ->with('user', function ($query) {
                $query->select(
                    'id',
                    'name',
                    'username',
                    'profile_image',
                    'biography',
                    'website',
                    'facebook',
                    'instagram',
                    'tiktok',
                    'twitter',
                    'linkedin',
                    'youtube',
                    'md5_vouchers_enabled',
                );
                $query->withCount('followers');
            })
            ->first();
    }
}
