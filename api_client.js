/**
 * TinyURL API Client
 * A JavaScript client for interacting with the TinyURL API
 */
class TinyUrlClient {
  /**
   * Create a new TinyURL API client
   * 
   * @param {string} apiKey - Your TinyURL API key
   * @param {string} baseUrl - Base URL of the TinyURL application
   */
  constructor(apiKey, baseUrl) {
    this.apiKey = apiKey;
    this.baseUrl = baseUrl;
  }

  /**
   * Make an API request
   * 
   * @param {string} endpoint - API endpoint
   * @param {object} data - Request data
   * @returns {Promise<object>} API response
   * @private
   */
  async _request(endpoint, data) {
    const response = await fetch(`${this.baseUrl}${endpoint}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        api_key: this.apiKey,
        ...data
      })
    });

    return await response.json();
  }

  /**
   * Shorten a URL
   * 
   * @param {string} url - The URL to shorten
   * @returns {Promise<object>} API response
   */
  async shortenUrl(url) {
    return await this._request('/api/shorten', { url });
  }

  /**
   * Get statistics for a shortened URL
   * 
   * @param {string} shortCode - The short code of the URL
   * @returns {Promise<object>} API response
   */
  async getUrlStats(shortCode) {
    return await this._request('/api/stats', { short_code: shortCode });
  }

  /**
   * List all URLs created with your API key
   * 
   * @param {number} limit - Number of URLs to return (default: 10)
   * @param {number} offset - Offset for pagination (default: 0)
   * @returns {Promise<object>} API response
   */
  async listUrls(limit = 10, offset = 0) {
    return await this._request('/api/list', { limit, offset });
  }
}

// Example usage
/*
const client = new TinyUrlClient('YOUR_API_KEY_HERE', 'http://yourdomain.com');

// Shorten a URL
client.shortenUrl('https://example.com/very/long/url')
  .then(response => {
    console.log('Shortened URL:', response.short_url);
    
    // Get statistics for the shortened URL
    return client.getUrlStats(response.short_code);
  })
  .then(stats => {
    console.log('URL Statistics:', stats);
    
    // List all URLs
    return client.listUrls(5, 0);
  })
  .then(urlList => {
    console.log('URL List:', urlList);
  })
  .catch(error => {
    console.error('Error:', error);
  });
*/