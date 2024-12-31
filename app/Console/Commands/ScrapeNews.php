<?php

namespace App\Console\Commands;

use App\Models\News;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class ScrapeNews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:news';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Coallation of news from various sources';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $sources = [
                'https://newsapi.org/v2/top-headlines?country=us&apiKey=3f35fc7c54204e028250043507ed9f12',
                'https://api.nytimes.com/svc/search/v2/articlesearch.json?api-key=AwdBshGw1r3c4GEnVIc0mwBbMraFZdvf',
                'https://content.guardianapis.com/search?api-key=75695965-b287-49bd-be5a-cf18ebc652bd'
            ];

            $client = new Client();

            foreach ($sources as $url) {
                try {
                    $response = $client->get($url);
                    $data = json_decode($response->getBody(), true);

                    if (str_contains($url, 'newsapi.org')) {
                        foreach ($data['articles'] as $article) {
                            News::updateOrCreate(
                                ['title' => $article['title']],
                                [
                                    'content' => $article['content'],
                                    'source' => $article['source']['name'] ?? 'Unknown',
                                    'author' => $article['author'] ?? 'Anonymous',
                                    'category' => $article['category'] ?? null,
                                    'published_at' => isset($article['publishedAt']) ? Carbon::parse($article['publishedAt'])->format('Y-m-d H:i:s') : now(),
                                ]
                            );
                        }
                    } elseif (str_contains($url, 'nytimes.com')) {
                        if (isset($data['response']['docs'])) {
                            foreach ($data['response']['docs'] as $article) {
                                News::updateOrCreate(
                                    ['title' => $article['headline']['main'] ?? 'Untitled'],
                                    [
                                        'content' => $article['lead_paragraph'] ?? null,
                                        'source' => 'New York Times',
                                        'author' => $article['byline']['original'] ?? 'Anonymous',
                                        'category' => null,
                                        'published_at' => isset($article['pub_date']) ? Carbon::parse($article['pub_date'])->format('Y-m-d H:i:s') : now(),
                                    ]
                                );
                            }
                        }
                    } elseif (str_contains($url, 'guardianapis.com')) {
                        if (isset($data['response']['results'])) {
                            foreach ($data['response']['results'] as $article) {
                                News::updateOrCreate(
                                    ['title' => $article['webTitle']],
                                    [
                                        'content' => $article['fields']['bodyText'] ?? null,
                                        'source' => 'The Guardian',
                                        'author' => $article['fields']['byline'] ?? 'Anonymous',
                                        'category' => $article['sectionName'] ?? null,
                                        'published_at' => isset($article['webPublicationDate']) ? Carbon::parse($article['webPublicationDate'])->format('Y-m-d H:i:s') : now(),
                                    ]
                                );
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $this->error("Failed to fetch from $url: " . $e->getMessage());
                }
            }

            $this->info('News scraping completed successfully.');
        } catch (\Exception $e) {
            $this->error("Failed to fetch from $url: " . $e->getMessage());
            $this->error("Stack Trace: " . $e->getTraceAsString());
        }
    }
}
