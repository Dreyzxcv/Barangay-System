import Link from "next/link";
import { Button } from "@/components/ui/button";

export default function HomePage() {
  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-gradient-to-br from-emerald-50 via-white to-slate-50 px-4">
      <div className="max-w-lg text-center">
        <div className="mb-6 inline-flex rounded-2xl bg-emerald-100 px-4 py-2 text-sm font-medium text-emerald-800">
          Barangay Digital Hub
        </div>
        <h1 className="mb-4 text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">
          Smart Barangay Dashboard
        </h1>
        <p className="mb-8 text-lg text-slate-600">
          Your central hub for announcements, service requests, community reports, and events.
        </p>
        <div className="flex flex-col gap-3 sm:flex-row sm:justify-center">
          <Button asChild size="lg">
            <Link href="/login">Sign In</Link>
          </Button>
          <Button asChild variant="outline" size="lg">
            <Link href="/register">Create Account</Link>
          </Button>
        </div>
      </div>
    </div>
  );
}
