import Link from "next/link";

export default function Tabs({ items = [], activeHref }) {
  return (
    <div className="flex flex-wrap items-center gap-2 rounded-lg bg-app-panel/70 p-1">
      {items.map((item) => {
        const isActive = activeHref === item.href;
        return (
          <Link
            key={item.href}
            href={item.href}
            className={`rounded-md px-4 py-2 text-xs transition ${
              isActive ? "bg-[rgba(252,205,3,0.18)] text-app-yellow" : "text-app-muted-light hover:bg-white/5 hover:text-white"
            }`}
          >
            {item.title}
          </Link>
        );
      })}
    </div>
  );
}
