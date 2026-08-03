<?php

namespace Concrete\Package\RtsCinemaSource\Block\WestWorldMedia;

use Concrete\Core\Block\BlockController;
use Concrete\Core\File\File;
use Concrete\Core\File\Importer;
use RtsCinemaSource\Service\IntegrationConfig;
use RtsCinemaSource\Service\RtsClient;

class Controller extends BlockController
{
    protected $btDescription = 'Gathers Cinema Source listing feed and converts it to JSON for use in scripting.';
    protected $btName = 'West World Media';
    protected $btInterfaceWidth = '600';
    protected $btInterfaceHeight = '400';

    public function getBlockTypeDescription()
    {
        return t('Integrates Cinema Source showtime data with RTS POS ticketing.');
    }

    public function view()
    {
        $html = $this->app->make('helper/html');
        $blockPath = $this->getBlockPath();

        $this->addFooterItem($html->css($blockPath . '/view.css'));
        $this->addFooterItem($html->javascript($blockPath . '/js/modal.js'));
        $this->addFooterItem($html->javascript($blockPath . '/js/script.js'));

        $this->buildListing();
    }

    private function buildListing(): void
    {
        $html = $this->app->make('helper/html');
        $expensiveCache = $this->app->make('cache/expensive');
        $integration = $this->app->make(IntegrationConfig::class);
        $rtsClient = $this->app->make(RtsClient::class);

        $all = $integration->getAll();
        $cinemaConfig = $all['cinema_source'];
        $rtsConfig = $all['rts'];

        $listCacheFile = DIR_FILES_UPLOADED_STANDARD . '/listingcache.js';
        $movieDataArr = [];
        $selDatesArr = [];
        $soonDatesArr = [];

        $updatedListingItem = $expensiveCache->getItem('movieFeed');
        $updatedListing = $updatedListingItem->get();

        if ($updatedListingItem->isMiss()) {
            $rtsListing = [];

            if ($cinemaConfig['api_key'] === '' || $cinemaConfig['house_id'] === '') {
                $this->set(
                    'errorMessage',
                    t('Configure Cinema Source API credentials in Dashboard → RTS Cinema Source.')
                );
            } else {
                $showTimeXml = '<?xml version="1.0"?><Request><Version>1</Version><Command>ShowTimeXml</Command><ShowAvalTickets>1</ShowAvalTickets><ShowSales>1</ShowSales><ShowSaleLinks>1</ShowSaleLinks></Request>';
                $rtsListing = $rtsClient->xmlToArray($rtsClient->postXml($rtsConfig, $showTimeXml));
                $listing = $rtsClient->xmlToArray($this->getWWMListingData($cinemaConfig));
                $movieListing = $listing['house']['schedule']['movie'] ?? [];

                if (isset($movieListing['movie_id'])) {
                    $movieListing = [$movieListing];
                }

                $updatedListing = $movieListing;

                foreach ($movieListing as $movie) {
                    if (empty($movie['movie_id'])) {
                        continue;
                    }

                    $movieId = $movie['movie_id'];
                    $movieDataItem = $expensiveCache->getItem('movieData' . $movieId);

                    if ($movieDataItem->isMiss()) {
                        $movieDataItem->lock();
                        $movieDataItem->set($this->getMovieDataWWM($movieId, $cinemaConfig), 86400);
                    }

                    $movieData = $movieDataItem->get();
                    $movieDataArr[$movieId] = $movieData;

                    foreach ($this->normalizeList($rtsListing['ShowSchedule']['Films']['Film'] ?? []) as $film) {
                        if (($film['CSCode'] ?? null) != $movieId) {
                            continue;
                        }

                        $shows = $this->normalizeList($film['Shows']['Show'] ?? []);
                        if (isset($film['Shows']['Show']['DT'])) {
                            $shows = [$film['Shows']['Show']];
                        }

                        foreach ($shows as $curShow) {
                            $tickets = $this->normalizeList($curShow['TIs']['TI'] ?? []);
                            if (isset($curShow['TIs']['TI']['C'])) {
                                $tickets = [$curShow['TIs']['TI']];
                            }

                            foreach ($tickets as $ti) {
                                $ticket = $this->ticketLookup($rtsListing, $ti['C'] ?? null);
                                $showDate = substr((string) ($curShow['DT'] ?? ''), 0, 8);
                                $showtimeEntries = $this->normalizeList($movie['showtimes'] ?? []);

                                if (isset($movie['showtimes']['@attributes'])) {
                                    $showtimeEntries = [$movie['showtimes']];
                                }

                                foreach ($showtimeEntries as $curShowTime) {
                                    $dateAttr = $curShowTime['@attributes']['date'] ?? null;
                                    if (!$dateAttr) {
                                        continue;
                                    }

                                    $stDateParts = explode('/', $dateAttr);
                                    if (count($stDateParts) !== 3) {
                                        continue;
                                    }

                                    $stDate = $stDateParts[2] . sprintf('%02d', $stDateParts[0]) . $stDateParts[1];
                                    if ($stDate !== $showDate) {
                                        continue;
                                    }

                                    if (!empty($ticket) && empty($ticket['HideOnInternet'])) {
                                        $selDatesArr[$dateAttr] = strtotime($dateAttr);
                                    } elseif (
                                        count($tickets) === 1 &&
                                        !empty($ticket) &&
                                        ($ticket['HideOnInternet'] ?? null) == 1 &&
                                        ($ticket['Name'] ?? '') === 'rSupersvr'
                                    ) {
                                        $soonDatesArr[$dateAttr] = strtotime($dateAttr);
                                    }
                                }
                            }
                        }
                    }

                    if (!empty($movieData['photos']['photo']) || !empty($movieData['hiphotos']['photo'])) {
                        $path = $this->resolvePosterPath($movieData);
                        if ($path) {
                            $movieDataArr[$movieId]['photos']['photo'] = $path;
                        }
                    }
                }

                $movieFeedItem = $expensiveCache->getItem('movieFeed');
                if ($movieFeedItem->isMiss()) {
                    $movieFeedItem->lock();
                    $movieFeedItem->set($updatedListing, 7200);
                }

                asort($selDatesArr);
                asort($soonDatesArr);

                $rtsConfigJs = json_encode($integration->getClientConfig(), JSON_UNESCAPED_SLASHES);
                $finalListing = 'var rtsConfig = ' . $rtsConfigJs . ";\n\n";
                $finalListing .= 'var dateOpts = ' . json_encode($selDatesArr) . ";\n\n";
                $finalListing .= 'var soonDateOpts = ' . json_encode($soonDatesArr) . ";\n\n";
                $finalListing .= 'var listingData = ' . json_encode($updatedListing) . ";\n\n";
                $finalListing .= 'var rtsListingData = ' . json_encode($rtsListing) . ";\n\n";
                $finalListing .= 'var movieData = ' . json_encode($movieDataArr) . ';';

                if (!is_dir(dirname($listCacheFile))) {
                    mkdir(dirname($listCacheFile), 0755, true);
                }
                file_put_contents($listCacheFile, $finalListing);
            }
        }

        $this->addFooterItem($html->javascript(DIR_REL . '/application/files/listingcache.js'));
    }

    private function buildCinemaSourceUrl(array $config, array $params): string
    {
        $query = array_merge(['apikey' => $config['api_key']], $params);

        return rtrim($config['base_url'], '/')
            . '/'
            . rawurlencode($config['api_version'])
            . '/?'
            . http_build_query($query);
    }

    private function getData(string $url): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $data = curl_exec($ch);
        curl_close($ch);

        return $data !== false ? $data : '';
    }

    private function getMovieDataWWM(string $movieId, array $config): array
    {
        $url = $this->buildCinemaSourceUrl($config, [
            'query' => 'movie',
            'stars' => 'yes',
            'photos' => 'all',
            'movie_id' => trim($movieId),
        ]);
        $rtsClient = $this->app->make(RtsClient::class);

        return $rtsClient->xmlToArray($this->getData($url))['movie'] ?? [];
    }

    private function getWWMListingData(array $config): string
    {
        return $this->getData($this->buildCinemaSourceUrl($config, [
            'query' => 'theater',
            'schedule' => 'yes',
            'house_id' => $config['house_id'],
            'sd' => 'yes',
            'showdate' => date('Ymd'),
            'enddate' => date('Ymd', strtotime('+4 months')),
        ]));
    }

    private function ticketLookup(array $rtsListing, $ticketID): ?array
    {
        if (empty($rtsListing['ShowSchedule']['Tickets']['Ticket'])) {
            return null;
        }

        $tickets = $rtsListing['ShowSchedule']['Tickets']['Ticket'];
        if (isset($tickets['Code'])) {
            $tickets = [$tickets];
        }

        foreach ($tickets as $ticket) {
            if (($ticket['Code'] ?? null) == $ticketID) {
                return $ticket;
            }
        }

        return null;
    }

    private function normalizeList($value): array
    {
        if (empty($value)) {
            return [];
        }

        if (isset($value[0]) || !is_array($value)) {
            return (array) $value;
        }

        return [$value];
    }

    private function findPosterFile(string $filename): ?File
    {
        $db = $this->app->make('database/connection');
        $row = $db->fetchAssoc(
            'SELECT fID FROM FileVersions WHERE fvIsApproved = 1 AND fvFilename = ? LIMIT 1',
            [$filename]
        );

        if (!$row || empty($row['fID'])) {
            return null;
        }

        $file = File::getByID((int) $row['fID']);

        return $file instanceof File ? $file : null;
    }

    private function importPoster(string $imageUrl, string $filename): ?string
    {
        $imageData = $this->getData($imageUrl);
        if ($imageData === '') {
            return null;
        }

        $cacheDir = DIR_FILES_UPLOADED_STANDARD . '/cache';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $cachePath = $cacheDir . '/' . $filename;
        file_put_contents($cachePath, $imageData);

        if (!file_exists($cachePath) || filesize($cachePath) === 0) {
            return null;
        }

        $importer = $this->app->make(Importer::class);
        if (method_exists($importer, 'importLocal')) {
            $newFile = $importer->importLocal($cachePath, $filename);
        } else {
            $newFile = $importer->import($cachePath, $filename);
        }

        @unlink($cachePath);

        if (!$newFile instanceof File) {
            return null;
        }

        $version = $newFile->getApprovedVersion();

        return $version ? $version->getRelativePath() : null;
    }

    private function resolvePosterPath(array $movieData): ?string
    {
        $filename = preg_replace('/[^A-Za-z0-9-]+/', '_', trim($movieData['name'] ?? '')) . '.jpg';
        $filename = preg_replace('/_+/', '_', $filename);

        $existing = $this->findPosterFile($filename);
        if ($existing instanceof File) {
            $version = $existing->getApprovedVersion();

            return $version ? $version->getRelativePath() : null;
        }

        $imgLnk = null;
        $hiPhotos = $movieData['hiphotos']['photo'] ?? null;
        $photos = $movieData['photos']['photo'] ?? null;

        if (is_array($hiPhotos)) {
            $imgLnk = $hiPhotos[0] ?? null;
        } elseif (!empty($hiPhotos)) {
            $imgLnk = $hiPhotos;
        } elseif (is_array($photos)) {
            $imgLnk = $photos[0] ?? null;
        } elseif (!empty($photos)) {
            $imgLnk = $photos;
        }

        if (empty($imgLnk)) {
            return null;
        }

        return $this->importPoster($imgLnk, $filename);
    }
}
