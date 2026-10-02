/**
 * Beha Frontend — Auth context + hooks.
 *
 * Sanctum SPA cookie-based flow:
 *   1. fetchCsrfToken() → GET /sanctum/csrf-cookie
 *   2. POST /api/v1/auth/login → sets session cookie
 *   3. GET /api/v1/auth/me → returns current user
 *   4. POST /api/v1/auth/logout → destroys session
 */

import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';
import { fetchCsrfToken, http, setBearerToken } from './api';
import type { User } from '../types';

interface AuthContextValue {
  user: User | null;
  loading: boolean;
  isAuthenticated: boolean;
  mustChangePassword: boolean;
  login: (identifier: string, password: string, remember?: boolean) => Promise<void>;
  logout: () => Promise<void>;
  refreshUser: () => Promise<void>;
  changePassword: (currentPassword: string, newPassword: string) => Promise<void>;
}

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const [mustChangePassword, setMustChangePassword] = useState(false);

  const refreshUser = useCallback(async () => {
    try {
      const { data } = await http.get<{ user: User | null; must_change_password?: boolean }>(
        '/auth/me',
      );
      setUser(data.user);
      setMustChangePassword(Boolean(data.must_change_password));
    } catch (err: unknown) {
      // 403 with must_change_password=true means the user IS authenticated but
      // must change their temp password before continuing — keep them "logged in"
      // so the ForcePasswordChangePage renders.
      const status = (err as { response?: { status?: number; data?: { must_change_password?: boolean; user?: User } } })?.response?.status;
      const mustChange = (err as { response?: { data?: { must_change_password?: boolean } } })?.response?.data?.must_change_password;
      if (status === 403 && mustChange) {
        const user = (err as { response?: { data?: { user?: User } } })?.response?.data?.user ?? null;
        setUser(user);
        setMustChangePassword(true);
      } else {
        // Genuinely unauthenticated — clear local state, the Protected route
        // wrapper will redirect to /login via React Router.
        setUser(null);
        setMustChangePassword(false);
      }
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    refreshUser();
  }, [refreshUser]);

  const login = useCallback(async (identifier: string, password: string, remember = false) => {
    // Step 1: fetch CSRF cookie
    await fetchCsrfToken();
    // Step 2: POST credentials
    const { data } = await http.post<{ user: User; must_change_password: boolean }>('/auth/login', {
      login: identifier,
      password,
      remember,
    });
    setUser(data.user);
    setMustChangePassword(Boolean(data.must_change_password));
  }, []);

  const logout = useCallback(async () => {
    try {
      await http.post('/auth/logout');
    } catch (err) {
      // ignore — we clear local state regardless
    }
    setUser(null);
    setMustChangePassword(false);
    setBearerToken(null);
  }, []);

  const changePassword = useCallback(async (currentPassword: string, newPassword: string) => {
    await http.post('/auth/change-password', {
      current_password: currentPassword,
      password: newPassword,
      password_confirmation: newPassword,
    });
    setMustChangePassword(false);
  }, []);

  const value = useMemo<AuthContextValue>(
    () => ({
      user,
      loading,
      isAuthenticated: user !== null,
      mustChangePassword,
      login,
      logout,
      refreshUser,
      changePassword,
    }),
    [user, loading, mustChangePassword, login, logout, refreshUser, changePassword],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

// eslint-disable-next-line react-refresh/only-export-components
export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used inside <AuthProvider>');
  return ctx;
}
