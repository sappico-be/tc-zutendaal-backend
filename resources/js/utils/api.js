import { ofetch } from 'ofetch';

export const $api = ofetch.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  credentials: 'include', // Belangrijk voor cookies
  async onRequest({ options }) {
    // Haal CSRF token uit cookie
    const token = document.cookie
      .split('; ')
      .find(row => row.startsWith('XSRF-TOKEN='))
      ?.split('=')[1];
    
    if (token) {
      options.headers = {
        ...options.headers,
        'X-XSRF-TOKEN': decodeURIComponent(token),
      };
    }

    // Add Bearer token als die er is
    const accessToken = useCookie('accessToken').value
    if (accessToken) {
      options.headers = {
        ...options.headers,
        'Authorization': `Bearer ${accessToken}`,
      };
    }
  },
})
