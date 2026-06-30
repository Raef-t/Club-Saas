"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";

function isActive(pathname, item) {
  if (item.href === "/accounting") return pathname === "/accounting";
  return pathname === item.href || pathname.startsWith(`${item.href}/`);
}

export default function Menu({ items }) {
  const pathname = usePathname();

  return (
    <nav className="space-y-4 text-sm">
      {items.map((item) => {
        const active = isActive(pathname, item);
        return (
          <div key={item.title}>
            <Link
              href={item.href}
              className={`mx-0 flex h-11 items-center justify-between rounded-lg px-5 transition ${active ? "gold-active" : "text-app-text hover:bg-white/5"}`}
            >
              <span className="text-lg text-app-yellow">‹</span>
              <span>{item.title}</span>
              <span className="text-lg text-app-yellow">›</span>
            </Link>

            {item.children && active && (
              <div className="mr-auto ml-7 mt-4 border-l border-white/40 py-1 pl-3">
                <div className="space-y-3">
                  {item.children.map((child) => {
                    const childActive = pathname === child.href;
                    return (
                      <Link key={child.title} href={child.href} className={`flex items-center justify-end gap-2 text-xs ${childActive ? "text-app-yellow" : "text-app-text"}`}>
                        <span>{child.title}</span>
                        <span className={`size-2 rounded-full border ${childActive ? "border-app-yellow" : "border-white/60"}`} />
                      </Link>
                    );
                  })}
                </div>
              </div>
            )}
          </div>
        );
      })}
    </nav>
  );
}
