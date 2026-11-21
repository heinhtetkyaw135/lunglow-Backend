<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class BurmeseMembersBlogSeeder extends Seeder
{
    public function run(): void
    {
        $burmeseUsers = [
            [
                'name' => 'Aung Kyaw Moe',
                'email' => 'aungkyawmoe@gmail.com',
                'avatar' => 'avatars/portrait-aung-kyaw-moe.jpg',
                'bio' => 'Yangon software engineer. Quitting smoking one morning tea at a time.',
                'resisted_count' => 34,
                'streak_days' => 14,
                'image' => 'blogs/lunglow-blog-1.svg',
                'with_image' => [
                    'title' => 'How I Broke My Morning Tea Shop Cigarette Habit in Yangon',
                    'category' => 'Tips & Tricks',
                    'content' => "Sitting at the corner tea shop with a cup of lahpet yay used to mean lighting up two cigarettes before 9 AM. For the past two weeks, I started ordering ginger tea instead and holding a roasted cinnamon stick in my right hand.\n\nWhenever the urge hits, I take three slow breaths and sip cold water right after the tea. Changing the flavor profile broke my automatic trigger loop, and my morning cough has already disappeared!",
                    'likes' => 24,
                ],
                'without_image' => [
                    'title' => 'Why Tracking Every Single Craving Window Changed My Mindset',
                    'category' => 'Success Story',
                    'content' => "Before using a craving heatmap, I felt like my urges were random and unstoppable. Once I logged them for seven days straight, I realized 80% of my cravings happened between 1:00 PM and 2:30 PM right after lunch, and around 9:00 PM before bed.\n\nKnowing the exact clock window means I no longer have to fight all day—I just prepare a 3-minute breathing session for those two windows. Two weeks clean and feeling sharper than ever.",
                    'likes' => 18,
                ],
            ],
            [
                'name' => 'Su Myat Noe',
                'email' => 'sumyatnoe@gmail.com',
                'avatar' => 'avatars/portrait-su-myat-noe.jpg',
                'bio' => 'Practicing 4-7-8 breathing every morning. Saving up for a family trip to Kalaw!',
                'resisted_count' => 42,
                'streak_days' => 19,
                'image' => 'blogs/lunglow-blog-2.svg',
                'with_image' => [
                    'title' => 'The 4-7-8 Breathing Technique That Stops Evening Cravings in 90 Seconds',
                    'category' => 'Health & Science',
                    'content' => "Most nicotine cravings peak like a wave and fade within three minutes if you do not feed them. Whenever my chest feels tight in the evening, I sit upright, inhale quietly through my nose for 4 seconds, hold for 7 seconds, and exhale slowly through pursed lips for 8 seconds.\n\nRepeating this cycle four times lowers your heart rate immediately and floods your bloodstream with fresh oxygen. It turns anxious craving energy into calm focus.",
                    'likes' => 31,
                ],
                'without_image' => [
                    'title' => 'Celebrating 19 Smoke-Free Days: What Changed in My Sleep and Skin',
                    'category' => 'Success Story',
                    'content' => "By day 10 without cigarettes, I noticed my hands and feet were warmer at night because my circulation improved. By day 19, I stopped waking up with a dry throat at 3 AM.\n\nEven my coworkers noticed my skin looks brighter and my voice sounds clearer during morning meetings. If you are on day 2 or 3 right now, hang in there—the physical rewards arrive much faster than you think!",
                    'likes' => 27,
                ],
            ],
            [
                'name' => 'Kyaw Swar Win',
                'email' => 'kyawswarwin@gmail.com',
                'avatar' => 'avatars/portrait-kyaw-swar-win.jpg',
                'bio' => 'Former pack-a-day smoker now running 5K around Kandawgyi Lake every weekend.',
                'resisted_count' => 51,
                'streak_days' => 25,
                'image' => 'blogs/lunglow-blog-3.svg',
                'with_image' => [
                    'title' => 'From Winded on Stairs to Running Around Kandawgyi Lake at Sunrise',
                    'category' => 'Success Story',
                    'content' => "A month ago, climbing two flights of stairs left me gasping for air. After cutting down and staying completely clean for 25 days, I completed a full morning jog around Kandawgyi boardwalk without stopping once.\n\nFeeling fresh air fill the bottom of my lungs without wheezing is worth more than any temporary nicotine rush. Every step forward builds stronger lungs.",
                    'likes' => 36,
                ],
                'without_image' => [
                    'title' => '3 Pocket Items I Carry Everywhere to Defeat Surprise Social Triggers',
                    'category' => 'Tips & Tricks',
                    'content' => "Social gatherings with friends who still smoke are the toughest test. I never leave home without three small items in my pocket: sugar-free strong peppermint gum, a small bottle of chilled water, and toothpicks infused with clove oil.\n\nKeeping your mouth busy the moment someone lights up nearby removes the awkwardness and keeps your streak intact.",
                    'likes' => 22,
                ],
            ],
            [
                'name' => 'Thiri Shwe Sin',
                'email' => 'thirishwesin@gmail.com',
                'avatar' => 'avatars/portrait-thiri-shwe-sin.jpg',
                'bio' => 'Nutritionist & wellness enthusiast. Helping my lungs heal with hydration and clean habits.',
                'resisted_count' => 29,
                'streak_days' => 12,
                'image' => 'blogs/lunglow-blog-4.svg',
                'with_image' => [
                    'title' => 'Cold Lime & Mint Water: How Hydration Flushes Residual Nicotine Faster',
                    'category' => 'Health & Science',
                    'content' => "During the first week of quitting or reducing cigarettes, staying hydrated is one of the fastest ways to help your kidneys flush cotinine and nicotine metabolites out of your system.\n\nI keep a tall glass bottle of ice water infused with fresh lime slices and crushed mint leaves on my desk. Every time my brain whispers 'smoke break,' I drink half a glass through a straw—the straw mimics the inhale motion while hydrating your airways.",
                    'likes' => 29,
                ],
                'without_image' => [
                    'title' => 'Why Post-Meal Cravings Happen and How to Reset Your Palate Immediately',
                    'category' => 'Tips & Tricks',
                    'content' => "Many smokers associate the feeling of a full stomach with lighting a cigarette right after lunch or dinner. The fastest way to break that neurological link is to brush your teeth or rinse with cool mint mouthwash immediately after eating.\n\nOnce your mouth feels clean and minty, cigarette smoke smells unappealing, giving your prefrontal cortex enough time to override the old habit.",
                    'likes' => 19,
                ],
            ],
            [
                'name' => 'Min Thu Aung',
                'email' => 'minthuaung@gmail.com',
                'avatar' => 'avatars/portrait-min-thu-aung.jpg',
                'bio' => 'Turning wasted cigarette money into new photography gear. Every clean day adds to the vault.',
                'resisted_count' => 38,
                'streak_days' => 16,
                'image' => 'blogs/lunglow-blog-5.svg',
                'with_image' => [
                    'title' => 'How My Glass Savings Jar Turned 320 Avoided Cigarettes Into Real Motivation',
                    'category' => 'Success Story',
                    'content' => "Numbers on a screen are great, but putting physical cash into a clear glass jar on my work desk every evening made my progress undeniable. In 16 days of staying under my cap and logging clean days, I have avoided over 320 cigarettes.\n\nSeeing the jar fill up towards my new headphones goal reminds me that smoking was literally burning my hard-earned money into ash.",
                    'likes' => 33,
                ],
                'without_image' => [
                    'title' => 'What to Do If You Have a One-Cigarette Slip Without Ruining Your Week',
                    'category' => 'Tips & Tricks',
                    'content' => "In the past, whenever I slipped and smoked one cigarette on a stressful day, I told myself 'I failed' and bought a whole pack. That all-or-nothing mindset is a trap.\n\nNow, if a slip happens, I log it honestly in the daily counter, study what triggered it, and make sure the next 24 hours are 100% clean. Progress is measured by total avoided cigarettes over months, not perfection on day one.",
                    'likes' => 25,
                ],
            ],
            [
                'name' => 'Hnin Wai Lwin',
                'email' => 'hninwailwin@gmail.com',
                'avatar' => 'avatars/portrait-hnin-wai-lwin.jpg',
                'bio' => 'Teacher from Mandalay. Walking 20 minutes every evening instead of smoking.',
                'resisted_count' => 27,
                'streak_days' => 11,
                'image' => 'blogs/lunglow-blog-6.svg',
                'with_image' => [
                    'title' => 'Replacing the 8 PM Balcony Smoke With a 15-Minute Evening Walk',
                    'category' => 'Tips & Tricks',
                    'content' => "My hardest daily trigger was standing on the balcony at 8 PM after finishing lesson plans. Because the location itself triggered my craving, I stopped going out to the balcony at night.\n\nInstead, I lace up my shoes and take a brisk 15-minute walk down my street while listening to an upbeat playlist. By the time I get back and shower, the craving window has completely passed.",
                    'likes' => 21,
                ],
                'without_image' => [
                    'title' => 'How Food Tastes and Smells Return After Just 7 Days Smoke-Free',
                    'category' => 'Health & Science',
                    'content' => "One of the most surprising joys of my first clean week was eating mohinga on Sunday morning and realizing how much richer the lemongrass and toasted chickpea aroma smelled.\n\nCigarette smoke dulls your olfactory receptors and taste buds within weeks of regular smoking, and they begin regenerating within 48 to 72 hours after you stop. Rediscovering real flavor is an amazing daily reward.",
                    'likes' => 26,
                ],
            ],
            [
                'name' => 'Zayar Phyo',
                'email' => 'zayarphyo@gmail.com',
                'avatar' => 'avatars/portrait-zayar-phyo.jpg',
                'bio' => 'Gamer & UI designer. Using quick memory puzzles to distract my brain during peak cravings.',
                'resisted_count' => 45,
                'streak_days' => 21,
                'image' => 'blogs/lunglow-blog-7.svg',
                'with_image' => [
                    'title' => 'Natural Herbal Alternatives That Satisfy the Hand-to-Mouth Routine',
                    'category' => 'Tips & Tricks',
                    'content' => "Half of cigarette addiction is chemical, and the other half is mechanical—the repetitive hand-to-mouth gesture every few minutes while thinking or working.\n\nI switched to keeping a small cup of roasted sunflower seeds, cinnamon sticks, and ginger slices next to my keyboard. Paired with warm green tea, my hands stay occupied during long design sessions without touching tobacco.",
                    'likes' => 28,
                ],
                'without_image' => [
                    'title' => 'How Playing a 2-Minute Memory Game Hijacks Working Memory During Cravings',
                    'category' => 'Health & Science',
                    'content' => "Cognitive psychology shows that visual cravings consume working memory in the brain. When you engage in a fast spatial task—like matching memory tiles or playing a quick tactical game for 120 seconds—your brain diverts bandwidth away from the craving loop.\n\nWhenever my afternoon urge hits level 2, I open the Lunglow exercises tab and play two rounds. By the second round, the urge drops by half.",
                    'likes' => 20,
                ],
            ],
            [
                'name' => 'May Thu Zar',
                'email' => 'maythuzar@gmail.com',
                'avatar' => 'avatars/portrait-may-thu-zar.jpg',
                'bio' => 'Medical student passionate about respiratory health and community quit-smoking support.',
                'resisted_count' => 36,
                'streak_days' => 15,
                'image' => 'blogs/lunglow-blog-8.svg',
                'with_image' => [
                    'title' => 'What Happens Inside Your Bronchial Cilia Between Day 3 and Day 14',
                    'category' => 'Health & Science',
                    'content' => "Microscopic hair-like structures called cilia line your bronchial tubes and sweep mucus and dust out of your lungs. Tar from cigarette smoke paralyzes and flattens these cilia, allowing toxins to settle deep in lung tissue.\n\nWithin 72 hours of quitting, cilia begin beating again. Don't be alarmed if you cough up clear mucus during week one—that is actually your lungs cleaning themselves out and restoring full oxygen exchange!",
                    'likes' => 39,
                ],
                'without_image' => [
                    'title' => 'Why Tapering Your Daily Limit Works Before Going Completely Zero-Smoke',
                    'category' => 'Tips & Tricks',
                    'content' => "For heavy smokers who smoke 20+ cigarettes a day, dropping straight to zero can trigger intense withdrawal headaches. Setting a strict daily cap in Lunglow—dropping from 20 to 12, then 6, then 0 over two weeks—trains delayed gratification.\n\nEach time you delay a cigarette by 45 minutes to stay under your daily limit, you weaken the conditioned reflex until stepping down to zero feels achievable.",
                    'likes' => 23,
                ],
            ],
            [
                'name' => 'Kaung Myat Thu',
                'email' => 'kaungmyatthu@gmail.com',
                'avatar' => 'avatars/portrait-kaung-myat-thu.jpg',
                'bio' => 'Accountant in Bago. Swapped stressful deadline smoke breaks for 3-minute breathing resets.',
                'resisted_count' => 31,
                'streak_days' => 13,
                'image' => 'blogs/lunglow-blog-9.svg',
                'with_image' => [
                    'title' => 'Surviving High-Stress Office Deadlines Without Stepping Out for a Smoke',
                    'category' => 'Tips & Tricks',
                    'content' => "We often believe cigarettes relieve work stress, but nicotine actually raises blood pressure and only relieves the withdrawal symptom it created 40 minutes earlier.\n\nDuring month-end accounting deadlines, I started taking a 3-minute standing stretch by an open window and doing box breathing (4s inhale, 4s hold, 4s exhale, 4s hold). I return to my spreadsheets with a lower heart rate and zero brain fog.",
                    'likes' => 25,
                ],
                'without_image' => [
                    'title' => 'The Hidden Financial Cost of a Half-Pack Habit Over 5 Years',
                    'category' => 'Success Story',
                    'content' => "As an accountant, I finally sat down and calculated the 5-year compound cost of smoking 10 to 15 cigarettes a day. Between packs, lighters, extra café drinks during smoke breaks, and health expenses, it adds up to thousands of dollars.\n\nRedirecting that exact daily amount into my Lunglow goal tracker gave me an immediate raise the day I quit.",
                    'likes' => 17,
                ],
            ],
            [
                'name' => 'Aye Myat Mon',
                'email' => 'ayemyatmon@gmail.com',
                'avatar' => 'avatars/portrait-aye-myat-mon.jpg',
                'bio' => 'Doing this for my family and my own long-term lung health. 17 days clean and counting!',
                'resisted_count' => 40,
                'streak_days' => 17,
                'image' => 'blogs/lunglow-blog-10.svg',
                'with_image' => [
                    'title' => 'Creating a 100% Smoke-Free Home Environment for My Family',
                    'category' => 'Success Story',
                    'content' => "My biggest turning point was learning how thirdhand smoke clings to curtains, sofa fabric, and clothes hours after a cigarette is put out. Two weeks ago, I washed every curtain in the house, threw away every ashtray and lighter, and placed indoor peace lily plants in the living room.\n\nComing home to a fresh, clean-smelling living room makes me proud every single day and keeps my family safe.",
                    'likes' => 34,
                ],
                'without_image' => [
                    'title' => 'How Accountability Partners in Community Challenges Double Your Quit Rate',
                    'category' => 'Tips & Tricks',
                    'content' => "Trying to quit alone in secret makes it too easy to give in when nobody is watching. Joining the 7-Day Clean Challenge and checking in every morning alongside other members gave me the daily accountability I was missing.\n\nWhenever you feel tempted, read two success stories on the Blog Feed and leave an encouraging comment for someone else—helping others strengthens your own resolve!",
                    'likes' => 22,
                ],
            ],
        ];

        $hasLegacyBlogImageCol = Schema::hasColumn('blogs', 'image');
        $hasLegacyUserCols = Schema::hasColumn('users', 'streak');
        $passwordHash = Hash::make('12345678');

        foreach ($burmeseUsers as $idx => $uData) {
            $streakDays = $uData['streak_days'];
            $dailyLimit = 20;
            $cigPrice = 0.50;

            $userAttrs = [
                'name' => $uData['name'],
                'password' => $passwordHash,
                'role' => 'member',
                'status' => 'active',
                'avatar' => $uData['avatar'] ?? null,
                'bio' => $uData['bio'],
                'resisted_count' => $uData['resisted_count'],
            ];
            if ($hasLegacyUserCols) {
                $userAttrs['streak'] = $streakDays;
                $userAttrs['saved_money'] = $streakDays * $dailyLimit * $cigPrice;
                $userAttrs['avoided_cigs'] = $streakDays * $dailyLimit;
            }

            $user = User::updateOrCreate(['email' => $uData['email']], $userAttrs);
            $user->settings()->updateOrCreate(
                ['user_id' => $user->id],
                ['daily_limit' => $dailyLimit, 'cig_price' => $cigPrice]
            );
            foreach (['morning' => '08:00', 'peak' => '13:00', 'evening' => '21:00'] as $key => $time) {
                $user->reminders()->updateOrCreate(
                    ['user_id' => $user->id, 'key' => $key],
                    ['time' => $time, 'enabled' => true]
                );
            }

            for ($d = 0; $d < $streakDays; $d++) {
                $dateStr = Carbon::now()->subDays($d)->format('Y-m-d');
                DB::table('smoke_logs')->updateOrInsert(
                    ['user_id' => $user->id, 'date' => $dateStr],
                    ['count' => 0, 'created_at' => Carbon::now()->subDays($d), 'updated_at' => Carbon::now()->subDays($d)]
                );
            }

            Blog::where('user_id', $user->id)->delete();

            $withImg = $uData['with_image'];
            $blog1 = Blog::create([
                'user_id' => $user->id,
                'title' => $withImg['title'],
                'category' => $withImg['category'],
                'author_role' => 'member',
                'images' => [$uData['image']],
                'content' => $withImg['content'],
                'likes' => $withImg['likes'],
            ]);
            if ($hasLegacyBlogImageCol) {
                DB::table('blogs')->where('id', $blog1->id)->update(['image' => $uData['image']]);
            }

            $noImg = $uData['without_image'];
            $blog2 = Blog::create([
                'user_id' => $user->id,
                'title' => $noImg['title'],
                'category' => $noImg['category'],
                'author_role' => 'member',
                'images' => [],
                'content' => $noImg['content'],
                'likes' => $noImg['likes'],
            ]);
            if ($hasLegacyBlogImageCol) {
                DB::table('blogs')->where('id', $blog2->id)->update(['image' => null]);
            }
        }
    }
}
