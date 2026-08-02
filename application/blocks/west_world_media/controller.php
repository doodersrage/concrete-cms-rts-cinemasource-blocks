<?php

namespace Application\Block\WestWorldMedia;

use Concrete\Core\Block\BlockController;
use Concrete\Core\File\File;
use Concrete\Core\File\Importer;

class Controller extends BlockController
{
    protected $btTable = 'btWestWorldMedia';
    protected $btDescription = 'Gathers Cinema Source listing feed and converts it to JSON for use in scripting.';
    protected $btName = 'West World Media';
    protected $btInterfaceWidth = '600';
    protected $btInterfaceHeight = '600';

    public $cinemaSourceApiKey;
    public $cinemaSourceApiVersion;
    public $cinemaSourceHouseId;
    public $rtsHost;
    public $rtsUsername;
    public $rtsPassword;
    public $rtsUseSandbox;
    public $processCompleteUrl;
    public $returnUrl;

    public function getBlockTypeDescription()
    {
        return t('Integrates Cinema Source showtime data with RTS POS ticketing.');
    }

    public function save($args)
    {
        $args['cinemaSourceApiVersion'] = trim($args['cinemaSourceApiVersion'] ?? '4.0') ?: '4.0';
        $args['rtsUseSandbox'] = !empty($args['rtsUseSandbox']) ? 1 : 0;
        parent::save($args);
    }

    public function view()
    {
        $this->buildListing();
    }

    private function getSiteConfig(): array
    {
        $path = DIR_APPLICATION . '/config/cinema_source.php';
        if (file_exists($path)) {
            $config = require $path;
            if (is_array($config)) {
                return $config;
            }
        }

        return [];
    }

    private function getIntegrationConfig(): array
    {
        $site = $this->getSiteConfig();

        return [
            'cinema_source' => [
                'base_url' => $site['cinema_source']['base_url'] ?? 'https://webservice.cinema-source.com',
                'api_version' => $this->cinemaSourceApiVersion ?: ($site['cinema_source']['api_version'] ?? '4.0'),
                'api_key' => $this->cinemaSourceApiKey ?: ($site['cinema_source']['api_key'] ?? ''),
                'house_id' => $this->cinemaSourceHouseId ?: ($site['cinema_source']['house_id'] ?? ''),
            ],
            'rts' => [
                'host' => $this->rtsHost ?: ($site['rts']['host'] ?? '72352.formovietickets.com'),
                'port' => (int) ($site['rts']['port'] ?? 2235),
                'username' => $this->rtsUsername ?: ($site['rts']['username'] ?? ''),
                'password' => $this->rtsPassword ?: ($site['rts']['password'] ?? ''),
                'use_sandbox' => (bool) ($this->rtsUseSandbox ?? ($site['rts']['use_sandbox'] ?? false)),
                'sandbox_host' => $site['rts']['sandbox_host'] ?? '5.formovietickets.com',
                'sandbox_username' => $site['rts']['sandbox_username'] ?? 'test',
                'sandbox_password' => $site['rts']['sandbox_password'] ?? 'test',
                'verify_ssl' => $site['rts']['verify_ssl'] ?? false,
            ],
            'site' => [
                'process_complete_url' => $this->processCompleteUrl ?: ($site['site']['process_complete_url'] ?? ''),
                'return_url' => $this->returnUrl ?: ($site['site']['return_url'] ?? ''),
                'conv_fee' => (float) ($site['site']['conv_fee'] ?? 1.35),
            ],
        ];
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

    private function xmlToArray(string $xml): array
    {
        if ($xml === '') {
            return [];
        }

        $element = simplexml_load_string($xml);
        if ($element === false) {
            return [];
        }

        return json_decode(json_encode($element), true) ?: [];
    }

    private function getMovieDataWWM(string $movieId, array $config): array
    {
        $url = $this->buildCinemaSourceUrl($config, [
            'query' => 'movie',
            'stars' => 'yes',
            'photos' => 'all',
            'movie_id' => trim($movieId),
        ]);
        $movieData = $this->xmlToArray($this->getData($url));

        return $movieData['movie'] ?? [];
    }

    private function getWWMListingData(array $config): string
    {
        $startDate = date('Ymd');
        $endDate = date('Ymd', strtotime('+4 months'));

        return $this->getData($this->buildCinemaSourceUrl($config, [
            'query' => 'theater',
            'schedule' => 'yes',
            'house_id' => $config['house_id'],
            'sd' => 'yes',
            'showdate' => $startDate,
            'enddate' => $endDate,
        ]));
    }

    private function getRtsEndpoint(array $rtsConfig): array
    {
        if (!empty($rtsConfig['use_sandbox'])) {
            return [
                'host' => $rtsConfig['sandbox_host'],
                'username' => $rtsConfig['sandbox_username'],
                'password' => $rtsConfig['sandbox_password'],
            ];
        }

        return [
            'host' => $rtsConfig['host'],
            'username' => $rtsConfig['username'],
            'password' => $rtsConfig['password'],
        ];
    }

    private function getRTSData(array $rtsConfig, string $packet = ''): string
    {
        if ($packet === 'ShowTimeXml') {
            $xml = new \SimpleXMLElement('<Request/>');
            $xml->addChild('Version', '1');
            $xml->addChild('Command', 'ShowTimeXml');
            $xml->addChild('ShowAvalTickets', '1');
            $xml->addChild('ShowSales', '1');
            $xml->addChild('ShowSaleLinks', '1');
            $packet = $xml->asXML();
        }

        $endpoint = $this->getRtsEndpoint($rtsConfig);
        $port = (int) ($rtsConfig['port'] ?? 2235);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://' . $endpoint['host'] . '/Data.ASP');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_PORT, $port);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, !empty($rtsConfig['verify_ssl']));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, !empty($rtsConfig['verify_ssl']) ? 2 : 0);
        curl_setopt($ch, CURLOPT_USERPWD, $endpoint['username'] . ':' . $endpoint['password']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/xml']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $packet);
        $data = curl_exec($ch);
        curl_close($ch);

        return $data !== false ? $data : '';
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

    private function buildListing(): void
    {
        $html = $this->app->make('helper/html');
        $expensiveCache = $this->app->make('cache/expensive');
        $integration = $this->getIntegrationConfig();
        $cinemaConfig = $integration['cinema_source'];
        $rtsConfig = $integration['rts'];

        $listCacheFile = DIR_FILES_UPLOADED_STANDARD . '/listingcache.js';

        $movieDataArr = [];
        $selDatesArr = [];
        $soonDatesArr = [];

        $updatedListingItem = $expensiveCache->getItem('movieFeed');
        $updatedListing = $updatedListingItem->get();

        if ($updatedListingItem->isMiss()) {
            $rtsListing = [];

            if ($cinemaConfig['api_key'] === '' || $cinemaConfig['house_id'] === '') {
                $this->set('errorMessage', t('Configure Cinema Source API credentials in the block or application/config/cinema_source.php.'));
            } else {
                $rtsListing = $this->xmlToArray($this->getRTSData($rtsConfig, 'ShowTimeXml'));
                $listing = $this->xmlToArray($this->getWWMListingData($cinemaConfig));
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
                                    } elseif (count($tickets) === 1 && !empty($ticket) && ($ticket['HideOnInternet'] ?? null) == 1 && ($ticket['Name'] ?? '') === 'rSupersvr') {
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

                $rtsConfigJs = json_encode([
                    'reqUrl' => '/rts/req.php',
                    'sessUrl' => '/rts/sess.php',
                    'redirUrl' => '/rts/redir.php',
                    'processCompleteUrl' => $integration['site']['process_complete_url'],
                    'returnUrl' => $integration['site']['return_url'],
                    'convFee' => $integration['site']['conv_fee'],
                ], JSON_UNESCAPED_SLASHES);

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
}
