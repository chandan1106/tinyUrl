#!/usr/bin/env python3
"""
TinyURL API Client
A Python client for interacting with the TinyURL API
"""

import requests
import json

class TinyUrlClient:
    """
    TinyURL API Client
    """
    
    def __init__(self, api_key, base_url):
        """
        Create a new TinyURL API client
        
        Args:
            api_key (str): Your TinyURL API key
            base_url (str): Base URL of the TinyURL application
        """
        self.api_key = api_key
        self.base_url = base_url
    
    def _request(self, endpoint, data):
        """
        Make an API request
        
        Args:
            endpoint (str): API endpoint
            data (dict): Request data
            
        Returns:
            dict: API response
        """
        # Add API key to data
        data['api_key'] = self.api_key
        
        # Make request
        response = requests.post(
            f"{self.base_url}{endpoint}",
            headers={'Content-Type': 'application/json'},
            data=json.dumps(data)
        )
        
        # Return JSON response
        return response.json()
    
    def shorten_url(self, url):
        """
        Shorten a URL
        
        Args:
            url (str): The URL to shorten
            
        Returns:
            dict: API response
        """
        return self._request('/api/shorten', {'url': url})
    
    def get_url_stats(self, short_code):
        """
        Get statistics for a shortened URL
        
        Args:
            short_code (str): The short code of the URL
            
        Returns:
            dict: API response
        """
        return self._request('/api/stats', {'short_code': short_code})
    
    def list_urls(self, limit=10, offset=0):
        """
        List all URLs created with your API key
        
        Args:
            limit (int, optional): Number of URLs to return. Defaults to 10.
            offset (int, optional): Offset for pagination. Defaults to 0.
            
        Returns:
            dict: API response
        """
        return self._request('/api/list', {'limit': limit, 'offset': offset})


# Example usage
if __name__ == "__main__":
    # Create client
    client = TinyUrlClient('YOUR_API_KEY_HERE', 'http://yourdomain.com')
    
    # Example 1: Shorten a URL
    print("Example 1: Shorten a URL")
    shorten_result = client.shorten_url('https://example.com/very/long/url')
    print(f"Shortened URL: {shorten_result.get('short_url')}")
    print()
    
    # Example 2: Get URL statistics
    if 'short_code' in shorten_result:
        print("Example 2: Get URL Statistics")
        short_code = shorten_result['short_code']
        stats_result = client.get_url_stats(short_code)
        print(f"Click count: {stats_result.get('click_count', 0)}")
        print()
    
    # Example 3: List URLs
    print("Example 3: List URLs")
    list_result = client.list_urls(5, 0)
    if 'urls' in list_result:
        print(f"Total URLs: {list_result.get('total', 0)}")
        print(f"URLs returned: {len(list_result.get('urls', []))}")
        for url in list_result.get('urls', []):
            print(f"- {url.get('short_url')} -> {url.get('click_count')} clicks")