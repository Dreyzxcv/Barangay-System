import { cn } from "@/lib/utils";

const variants: Record<string, string> = {
  default: "bg-slate-100 text-slate-800",
  success: "bg-emerald-100 text-emerald-800",
  warning: "bg-amber-100 text-amber-800",
  danger: "bg-red-100 text-red-800",
  info: "bg-blue-100 text-blue-800",
};

export function Badge({
  className,
  variant = "default",
  ...props
}: React.HTMLAttributes<HTMLSpanElement> & { variant?: keyof typeof variants }) {
  return (
    <span
      className={cn(
        "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium",
        variants[variant],
        className
      )}
      {...props}
    />
  );
}

export function statusVariant(status: string): keyof typeof variants {
  switch (status) {
    case "resolved":
    case "completed":
      return "success";
    case "in_progress":
    case "reviewing":
    case "ready_for_pickup":
      return "warning";
    case "closed":
      return "default";
    case "pending":
    case "submitted":
      return "info";
    default:
      return "default";
  }
}
