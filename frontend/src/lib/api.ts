/**
 * Beha Frontend — Axios HTTP client + Sanctum SPA cookie auth.
 *
 * Sanctum cookie auth requires `withCredentials: true` on EVERY request so
 * the session cookie + CSRF cookie are sent. The CSRF token from the
 * XSRF-TOKEN cookie is auto-included by axios for unsafe methods (POST/PUT/DELETE).
 */

import axios, { AxiosError, type AxiosInstance, type InternalAxiosRequestConfig } from 'axios';

export const API_BASE_URL = import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api/v1';
export const API_ROOT_URL = new URL(API_BASE_URL).origin;

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

// Response interceptor — handle 419 CSRF mismatch by silently refreshing the cookie.
//
// 401 handling is delegated to the AuthProvider + Protected route wrapper —
// the React state machine redirects to /login via React Router, avoiding
// full-page reloads.
http.interceptors.response.use(
  (response) => response,
  (error: AxiosError) => {
    if (error.response?.status === 419) {
      console.error('CSRF token mismatch (419). Refreshing cookie — please retry.');
      // Refresh CSRF cookie silently so the next retry succeeds.
      axios
        .get(`${API_ROOT_URL}/sanctum/csrf-cookie`, {
          withCredentials: true,
        })
        .catch(() => {});
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
  await axios.get(`${API_ROOT_URL}/sanctum/csrf-cookie`, {
    withCredentials: true,
  });
}
