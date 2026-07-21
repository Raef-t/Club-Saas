import StatCard from "@/components/ui/StatCard";

export default function StatsGrid({ items = [], className = "" }) {
  return (
    <section className={`grid grid-cols-[repeat(auto-fit,minmax(min(100%,186px),1fr))] gap-3 sm:gap-5 ${className}`}>
      {items.map((item) => (
        <StatCard key={`${item.title}-${item.value}`} {...item} />
      ))}
    </section>
  );
}
