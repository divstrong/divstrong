import React, { createContext, useCallback, useContext, useEffect, useState } from 'react';
import { getToken } from '../api/client';
import * as AuthApi from '../api/auth';

type AuthState = {
  user: AuthApi.AuthUser | null;
  loading: boolean;
  initError: string | null;
  signIn: (email: string, password: string) => Promise<void>;
  signOut: () => Promise<void>;
};

const AuthContext = createContext<AuthState | undefined>(undefined);

function withTimeout<T>(p: Promise<T>, ms: number, label: string): Promise<T> {
  return Promise.race([
    p,
    new Promise<T>((_, reject) => setTimeout(() => reject(new Error(`${label} timed out`)), ms)),
  ]);
}

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<AuthApi.AuthUser | null>(null);
  const [loading, setLoading] = useState(true);
  const [initError, setInitError] = useState<string | null>(null);

  useEffect(() => {
    (async () => {
      try {
        const token = await withTimeout(getToken(), 4000, 'SecureStore');
        if (token) {
          const u = await withTimeout(AuthApi.me(), 6000, 'Fetch /auth/me');
          setUser(u);
        }
      } catch (e: any) {
        console.warn('[auth init]', e?.message ?? e);
        setUser(null);
        setInitError(e?.message ?? 'Startup error');
      } finally {
        setLoading(false);
      }
    })();
  }, []);

  const signIn = useCallback(async (email: string, password: string) => {
    const u = await AuthApi.login(email, password);
    setUser(u);
  }, []);

  const signOut = useCallback(async () => {
    await AuthApi.logout();
    setUser(null);
  }, []);

  return (
    <AuthContext.Provider value={{ user, loading, initError, signIn, signOut }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth(): AuthState {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used within AuthProvider');
  return ctx;
}
