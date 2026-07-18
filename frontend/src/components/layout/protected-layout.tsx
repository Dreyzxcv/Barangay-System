"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { useAuth } from "@/lib/auth";
import { AppShell } from "./app-shell";

export function ProtectedLayout({ children, adminOnly = false }: { children: React.ReactNode; adminOnly?: boolean }) {
  const { user, loading } = useAuth();
  const router = useRouter();

  useEffect(() => {
    if (!loading && !user) {
      router.replace("/login");
    }
    if (!loading && user && adminOnly && user.role !== "admin") {
      router.replace("/dashboard");
    }
    if (!loading && user && !adminOnly && user.role === "admin" && !window.location.pathname.startsWith("/admin") && !window.location.pathname.startsWith("/notifications")) {
      // Allow admin to access notifications
    }
  }, [user, loading, router, adminOnly]);

  if (loading || !user) {
    return (
      <div className="flex min-h-screen items-center justify-center">
        <div className="h-8 w-8 animate-spin rounded-full border-4 border-emerald-600 border-t-transparent" />
      </div>
    );
  }

  if (adminOnly && user.role !== "admin") return null;

  return <AppShell>{children}</AppShell>;
}
