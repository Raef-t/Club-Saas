import StatCard from "@/components/ui/StatCard";

const gridVariants = {
  default: "grid-cols-[repeat(auto-fit,minmax(min(100%,186px),1fr))]",
  compact: "grid-cols-[repeat(auto-fit,minmax(min(100%,172px),1fr))]",
  wide: "grid-auto-fit",
};

/**
 * Renders a responsive collection of statistic cards.
 */
export default function StatsGrid({ items = [], className = "", variant = "default" }) {
  const gridClassName = gridVariants[variant] ?? gridVariants.default;

  return (
    <section className={`grid ${gridClassName} gap-3 sm:gap-5 ${className}`}>
      {items.map((item) => (
        <StatCard key={`${item.title}-${item.value}`} {...item} />
      ))}
    </section>
  );
}
