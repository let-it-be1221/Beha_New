/**
 * Beha Frontend — Axios HTTP client + Sanctum SPA cookie auth.
 *
 * Sanctum cookie auth requires `withCredentials: true` on EVERY request so
 * the session cookie + CSRF cookie are sent. The CSRF token from the
 * XSRF-TOKEN cookie is auto-included by axios for unsafe methods (POST/PUT/DELETE).
 */

import axios, { AxiosError, type AxiosInstance, type InternalAxiosRequestConfig } from 'axios';

const API_BASE_URL = import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api/v1';

export const http: AxiosInstance = axios.create({
  baseURL: API_BASE_URL,
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
  timeout: 15000,
});

// Attach auth token (if used by mobile/external clients — SPA uses cookies)
let bearerToken: string | null = null;
export function setBearerToken(token: string | null): void {
  bearerToken = token;
  if (token) {
    http.defaults.headers.common.Authorization = `Bearer ${token}`;
  } else {
    delete http.defaults.headers.common.Authorization;
  }
}

// Request interceptor — attach Bearer token if set
http.interceptors.request.use((config: InternalAxiosRequestConfig) => {
  if (bearerToken && !config.headers.Authorization) {
    config.headers.Authorization = `Bearer ${bearerToken}`;
  }
  return config;
});

// Response interceptor — handle 401 by redirecting to login
http.interceptors.response.use(
  (response) => response,
  (error: AxiosError) => {
    if (error.response?.status === 401) {
      // Only redirect if we're not already on a login page
      if (!window.location.pathname.startsWith('/login')) {
        // Clear any stale token
        setBearerToken(null);
        // Redirect to login page (React Router will pick this up)
        window.location.href = '/login';
      }
    }
    if (error.response?.status === 419) {
      // CSRF token mismatch — refresh the CSRF cookie then prompt user to retry
      console.error('CSRF token mismatch (419). Refreshing cookie — please retry your action.');
      // Re-fetch the CSRF cookie silently
      http.get('/sanctum/csrf-cookie' as never, { baseURL: 'http://localhost:8000' } as never).catch(() => {});
    }
    return Promise.reject(error);
  },
);

/**
 * Fetch the Sanctum CSRF cookie. MUST be called once before the first POST.
 * The cookie is set as XSRF-TOKEN; axios auto-includes it on subsequent
 * unsafe requests as the X-XSRF-TOKEN header.
 */
export async function fetchCsrfToken(): Promise<void> {
  // The csrf-cookie endpoint is on the Laravel root (not /api/v1)
  await axios.get(`${import.meta.env.VITE_API_URL ?? 'http://localhost:8000'}/sanctum/csrf-cookie`, {
    withCredentials: true,
  });
}
