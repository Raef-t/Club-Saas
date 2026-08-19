"use client";

import { useMemo, useState } from "react";
import StatsGrid from "@/components/ui/StatsGrid";
import SearchInput from "@/components/ui/SearchInput";
import LoadingSpinner from "@/components/ui/LoadingSpinner";
import { useGetPlayerSubscriptionsQuery } from "@/lib/api/playerSubscriptionsApi";
import { useManagementBranch } from "@/lib/ManagementBranchContext";

export default function SubscriptionsAccountingClient({ initialSubscriptions = [] }) {
  const { selectedBranchId } = useManagementBranch();
  const [search, setSearch] = useState("");
  const [statusFilter, setStatusFilter] = useState("all");

  const queryParams = useMemo(() => {
    const params = {};
    if (selectedBranchId && selectedBranchId !== "all") params.branch_id = selectedBranchId;
    return params;
  }, [selectedBranchId]);

  const { data, isLoading } = useGetPlayerSubscriptionsQuery(queryParams);

  const rawList = data?.data?.data || data?.data || initialSubscriptions;
  const subscriptions = Array.isArray(rawList) ? rawList : [];

  const filteredSubscriptions = useMemo(() => {
    return subscriptions.filter((sub) => {
      const playerName =
        sub.player?.person?.full_name ||
        `${sub.player?.person?.first_name || ""} ${sub.player?.person?.last_name || ""}`.trim() ||
        sub.player_name ||
        "";

      const matchSearch =
        !search.trim() ||
        playerName.toLowerCase().includes(search.toLowerCase()) ||
        sub.plan?.name?.toLowerCase().includes(search.toLowerCase());

      const matchStatus = statusFilter === "all" || sub.status === statusFilter;

      return matchSearch && matchStatus;
    });
  }, [subscriptions, search, statusFilter]);

  const totalPaid = subscriptions.reduce((sum, s) => sum + Number(s.paid_amount || 0), 0);
  const totalRemaining = subscriptions.reduce((sum, s) => sum + Number(s.remaining_amount || 0), 0);

  const statsItems = [
    { label: "إجمالي الاشتراكات", value: subscriptions.length },
    { label: "إجمالي المبالغ المحصلة", value: `$${totalPaid.toLocaleString()}` },
    { label: "الذمم المتبقية غير المحصلة", value: `$${totalRemaining.toLocaleString()}` },
    { label: "الاشتراكات النشطة", value: subscriptions.filter((s) => s.status === "active").length },
  ];

  return (
    <div className="space-y-6" dir="rtl">
      <StatsGrid items={statsItems} />

      <div className="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-app-line/40 bg-app-card-soft/40 p-4">
        <div className="w-full sm:w-80">
          <SearchInput
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="البحث باسم العضو أو نوع الاشتراك..."
          />
        </div>

        <div className="flex items-center gap-1.5">
          {["all", "active", "pending", "frozen", "expired", "cancelled"].map((st) => (
            <button
              key={st}
              onClick={() => setStatusFilter(st)}
              className={`rounded-xl px-3 py-1.5 text-xs font-medium transition ${
                statusFilter === st
                  ? "bg-app-yellow text-app-bg font-bold shadow"
                  : "bg-app-card text-app-muted hover:text-app-text"
              }`}
            >
              {st === "all" ? "الكل" : st === "active" ? "نشط" : st === "pending" ? "معلق" : st === "frozen" ? "مجمد" : st === "expired" ? "منتهي" : "ملغي"}
            </button>
          ))}
        </div>
      </div>

      <div className="rounded-2xl border border-app-line/40 bg-app-panel p-5">
        <div className="mb-4 flex items-center justify-between border-b border-app-line/30 pb-3">
          <div>
            <h2 className="text-lg font-bold text-app-text">مدفوعات اشتراكات الأعضاء</h2>
            <p className="text-xs text-app-muted">
              حركات المبالغ المحصلة والمتبقية من اشتراكات اللاعبين في الفرع
            </p>
          </div>
          <span className="rounded-lg bg-app-card-soft px-3 py-1 text-xs font-mono text-app-muted-light">
            {filteredSubscriptions.length} اشتراك
          </span>
        </div>

        {isLoading ? (
          <div className="flex h-64 items-center justify-center">
            <LoadingSpinner />
          </div>
        ) : filteredSubscriptions.length === 0 ? (
          <div className="rounded-xl border border-dashed border-app-line/60 p-12 text-center text-app-muted">
            لا توجد اشتراكات مطابقة لمعايير البحث في هذا الفرع.
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-right text-sm">
              <thead className="border-b border-app-line/40 text-xs text-app-muted">
                <tr>
                  <th className="p-3">اسم العضو / اللاعب</th>
                  <th className="p-3">الباقة / الخطة</th>
                  <th className="p-3">تاريخ البداية</th>
                  <th className="p-3">تاريخ النهاية</th>
                  <th className="p-3 text-left">الإجمالي</th>
                  <th className="p-3 text-left">المدفوع</th>
                  <th className="p-3 text-left">المتبقي (ذمة)</th>
                  <th className="p-3">الحالة</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-app-line/20 text-app-text text-xs">
                {filteredSubscriptions.map((sub) => {
                  const playerName =
                    sub.player?.person?.full_name ||
                    `${sub.player?.person?.first_name || ""} ${sub.player?.person?.last_name || ""}`.trim() ||
                    sub.player_name ||
                    `لاعب #${sub.player_id}`;

                  const total = Number(sub.total_amount || 0);
                  const paid = Number(sub.paid_amount || 0);
                  const remaining = Number(sub.remaining_amount || 0);

                  return (
                    <tr key={sub.id} className="hover:bg-app-card-soft/40 transition">
                      <td className="p-3 font-medium text-app-text">{playerName}</td>
                      <td className="p-3 text-app-muted-light">{sub.plan?.name || "باقة عامة"}</td>
                      <td className="p-3 font-mono text-app-muted">{sub.start_date || "-"}</td>
                      <td className="p-3 font-mono text-app-muted">{sub.end_date || "-"}</td>
                      <td className="p-3 text-left font-mono font-bold text-app-text">
                        ${total.toLocaleString()}
                      </td>
                      <td className="p-3 text-left font-mono font-bold text-emerald-400">
                        ${paid.toLocaleString()}
                      </td>
                      <td className="p-3 text-left font-mono font-bold text-rose-400">
                        {remaining > 0 ? `$${remaining.toLocaleString()}` : "$0"}
                      </td>
                      <td className="p-3">
                        <span className="rounded-full bg-app-card-soft px-2.5 py-0.5 text-[10px] font-medium border border-app-line/30 text-app-muted-light">
                          {sub.status || "active"}
                        </span>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}
