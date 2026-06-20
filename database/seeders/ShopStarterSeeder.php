<?php

namespace Database\Seeders;

use App\Models\Shop\WebsiteShopArticle;
use App\Models\Shop\WebsiteShopCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds a starter store: Credits / Duckets / Diamonds / VIP categories with sensible packages.
 * Idempotent — safe to re-run (matches categories by slug and articles by name).
 *
 * `costs` is stored in CENTS (WebsiteShopArticle::price() divides by 100). give_rank is intentionally
 * left null on every package — the only roles that exist are staff (User/Moderator/Admin), so the
 * shop must never grant a rank. Edit/extend freely afterwards.
 */
class ShopStarterSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'credits'  => ['name' => 'Credits',  'icon' => '/assets/images/icons/credits.png'],
            'duckets'  => ['name' => 'Duckets',  'icon' => '/assets/images/icons/duckets.png'],
            'diamonds' => ['name' => 'Diamonds', 'icon' => '/assets/images/icons/diamond.png'],
            'vip'      => ['name' => 'VIP',       'icon' => '/client/assets/c_images/album1584/ACH_BasicClub5.gif'],
        ];

        // Ensure the VIP bundle's club badge code exists in `badges` so the purchase can grant it
        // (the gif is already in c_images/album1584). badges.code is longtext, so guard by lookup.
        if (! DB::table('badges')->where('code', 'ACH_BasicClub5')->exists()) {
            DB::table('badges')->insert(['code' => 'ACH_BasicClub5']);
        }

        $catModels = [];
        foreach ($categories as $slug => $data) {
            $catModels[$slug] = WebsiteShopCategory::updateOrCreate(
                ['slug' => $slug],
                ['name' => $data['name'], 'icon' => $data['icon']],
            );
        }

        $creditIcon  = '/assets/images/icons/credits.png';
        $ducketIcon  = '/assets/images/icons/duckets.png';
        $diamondIcon = '/assets/images/icons/diamond.png';
        $vipIcon     = '/client/assets/c_images/album1584/ACH_BasicClub5.gif';

        // [category slug, name, info, icon, color, costs(cents), credits, duckets, diamonds, badges, giftable, position, features[]]
        $articles = [
            ['credits', 'Starter Credits', '5,000 Credits',   $creditIcon, '#f5a623', 500,  5000,   0,   0, null, 1, 1, ['5,000 Credits', 'Instant delivery']],
            ['credits', 'Value Credits',   '15,000 Credits',  $creditIcon, '#f5a623', 1000, 15000,  0,   0, null, 1, 2, ['15,000 Credits', 'Best value', 'Instant delivery']],
            ['credits', 'Mega Credits',    '50,000 Credits',  $creditIcon, '#f5a623', 2500, 50000,  0,   0, null, 1, 3, ['50,000 Credits', 'Huge savings', 'Instant delivery']],

            ['duckets', 'Ducket Pouch',    '25,000 Duckets',  $ducketIcon, '#5b8def', 300,  0, 25000,   0, null, 1, 1, ['25,000 Duckets', 'Instant delivery']],
            ['duckets', 'Ducket Chest',    '100,000 Duckets', $ducketIcon, '#5b8def', 800,  0, 100000,  0, null, 1, 2, ['100,000 Duckets', 'Instant delivery']],

            ['diamonds', 'Diamond Bag',    '50 Diamonds',     $diamondIcon, '#59d0d6', 500,  0, 0,  50, null, 1, 1, ['50 Diamonds', 'Instant delivery']],
            ['diamonds', 'Diamond Hoard',  '200 Diamonds',    $diamondIcon, '#59d0d6', 1500, 0, 0, 200, null, 1, 2, ['200 Diamonds', 'Best value', 'Instant delivery']],

            ['vip', 'VIP Bundle', 'Credits + Diamonds + Duckets + Club badge', $vipIcon, '#f5b73d', 2000, 10000, 50000, 100, 'ACH_BasicClub5', 1, 1,
                ['10,000 Credits', '50,000 Duckets', '100 Diamonds', 'Exclusive Club badge', 'Giftable']],
        ];

        foreach ($articles as $a) {
            [$slug, $name, $info, $icon, $color, $costs, $credits, $duckets, $diamonds, $badges, $giftable, $position, $features] = $a;

            $article = WebsiteShopArticle::updateOrCreate(
                ['name' => $name],
                [
                    'website_shop_category_id' => $catModels[$slug]->id,
                    'info'        => $info,
                    'icon_url'    => $icon,
                    'color'       => $color,
                    'costs'       => $costs,
                    'give_rank'   => null,
                    'credits'     => $credits,
                    'duckets'     => $duckets,
                    'diamonds'    => $diamonds,
                    'badges'      => $badges,
                    'furniture'   => null,
                    'position'    => $position,
                    'is_giftable' => $giftable,
                ],
            );

            $article->features()->delete();
            foreach ($features as $content) {
                $article->features()->create(['content' => $content]);
            }
        }
    }
}
