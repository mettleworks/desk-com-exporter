<?php namespace Mettleworks\DeskComExporter;

use GuzzleHttp\Client as HttpClient;

class DeskComExporter
{

    /**
     * http://dev.desk.com/API/using-the-api/#general
     *
     * X-Rate-Limit-Reset - seconds remaining until the next window begins
     *
     * @var int
     */
    protected $rateLimitResetSeconds = 0;

    /**
     * http://dev.desk.com/API/using-the-api/#general
     *
     * X-Rate-Limit-Remaining - available requests remaining in the current window
     *
     * @var int
     */
    protected $rateLimitRemaining = 1;

    /**
     * @var HttpClient $guzzleClient
     */
    protected $httpClient;

    /**
     * @var int
     */
    protected $perPage = 50;

    /**
     * @param HttpClient $guzzleClient
     */
    public function __construct(HttpClient $guzzleClient)
    {
        $this->httpClient = $guzzleClient;
    }

    /**
     * @param callable $callback
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function fetchCases(callable $callback)
    {
        $this->iterateCaseRequests('/api/v2/cases', $callback);
    }

    /**
     * @param callable $callback
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function fetchCustomers(callable $callback)
    {
        $this->iterateCustomerRequests('/api/v2/customers/search', $callback, null, [
            'q' => '*'
        ]);
    }

    /**
     * @param $limit
     */
    public function changeDefinedPageLimit($limit)
    {
        $this->perPage = $limit;
    }

    /**
     * @param $endpoint
     * @param callable $callback
     * @param null $from
     * @param array $query
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function iterateCustomerRequests($endpoint, callable $callback, $from = null, array $query = [])
    {
        if ($from)
        {
            $query['since_id'] = $from;
        }

        $query['sort_field'] = 'id';
        $query['sort_direction'] = 'asc';

        $results = $this->requestResults($endpoint, $query);

        if ( ! isset($results['_embedded']['entries']))
        {
            throw new \RuntimeException('Invalid response: ' . json_encode($results));
        }

        if (count($results['_embedded']['entries']) < $this->perPage)
        {
            return $callback($results, true);
        }

        $values = array_values($results['_embedded']['entries']);
        $lastElement = end($values);
        $lastId = isset($lastElement['id']) ? $lastElement['id'] : null;

        if ($lastId)
        {
            $callback($results, false);

            $sinceId = $lastId + 1;

            // Desk.com ignores `since_id` value if `q` is set
            // but either `q` or `since_id` is required
            // so we specify `q` for the initial request
            unset($query['q']);

            return $this->iterateCustomerRequests($endpoint, $callback, $sinceId, $query);
        }

        return $callback($results, true);
    }

    /**
     * @param $endpoint
     * @param callable $callback
     * @param null $from
     * @param array $query
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function iterateCaseRequests($endpoint, callable $callback, $from = null, array $query = [])
    {
        if ($from)
        {
            $query['since_id'] = $from;
        }

        $query['sort_field'] = 'id';
        $query['sort_direction'] = 'asc';

        $results = $this->requestResults($endpoint, $query);

        if ( ! isset($results['_embedded']['entries']))
        {
            throw new \RuntimeException('Invalid response: ' . json_encode($results));
        }

        if (count($results['_embedded']['entries']) < $this->perPage)
        {
            return $callback($results, true);
        }

        $values = array_values($results['_embedded']['entries']);
        $lastElement = end($values);
        $lastId = isset($lastElement['id']) ? $lastElement['id'] : null;

        if ($lastId)
        {
            $callback($results, false);

            $sinceId = $lastId + 1;

            return $this->iterateCaseRequests($endpoint, $callback, $sinceId, $query);
        }

        return $callback($results, true);
    }

    /**
     * @param $endpoint
     * @param array $query
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function requestResults($endpoint, array $query = [])
    {
        $this->checkRateLimit();

        $response = $this->httpClient->request('GET', $endpoint, [
            'query' => $query
        ]);

        $this->rateLimitRemaining = (int)$response->getHeaderLine('X-Rate-Limit-Remaining');
        $this->rateLimitResetSeconds = (int)$response->getHeaderLine('X-Rate-Limit-Reset');

        return json_decode($response->getBody(), true);
    }

    /**
     * @return void
     */
    protected function checkRateLimit()
    {
        if (is_null($this->rateLimitRemaining))
        {
            return;
        }

        if ( ! $this->rateLimitRemaining)
        {
            sleep($this->rateLimitResetSeconds + 1);
        }
    }
}