import Link from "next/link";
import LoadingSpinner from "@/components/ui/LoadingSpinner";

const toneClass = {
  primary: "app-button-primary hover:bg-app-yellow/90",
  dark: "app-button-dark hover:bg-white/10",
  ghost: "bg-app-card-soft text-app-text hover:bg-white/10",
  outline: "border border-app-line bg-app-panel-soft text-app-muted-light hover:border-app-yellow/60 hover:text-app-yellow",
  danger: "bg-app-red/15 text-app-red hover:bg-app-red/20",
};

export default function Button({
  href,
  children,
  icon,
  className = "",
  tone = "primary",
  loading = false,
  loadingLabel = "جاري التحميل",
  disabled,
  ...props
}) {
  const classes = `inline-flex h-10 items-center justify-center gap-2 rounded-lg px-4 text-sm font-medium transition disabled:cursor-not-allowed disabled:opacity-70 ${toneClass[tone] || toneClass.primary} ${className}`;
  const content = loading ? (
    <LoadingSpinner className="size-5" label={loadingLabel} />
  ) : (
    <>
      {icon}
      {children}
    </>
  );

  if (href) {
    return (
      <Link href={href} className={classes} aria-busy={loading} {...props}>
        {content}
      </Link>
    );
  }

  return (
    <button className={classes} disabled={disabled || loading} aria-busy={loading} {...props}>
      {content}
    </button>
  );
}
