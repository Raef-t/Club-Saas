import SectionCard from "@/components/ui/SectionCard";
import StatsGrid from "@/components/ui/StatsGrid";
import LineChart from "@/components/charts/LineChart";
import { GridIcon, TrendUpIcon } from "@/components/icons/Icons";
import { branchComparison, branchMiniCards, branchStats, reportStats } from "@/data/mockData";

function BranchSummaryCard({ title, helper }) {
  return (
    <article className="card-shell rounded-2xl p-5">
      <div className="flex items-start justify-between gap-4">
        <div className="grid size-11 place-items-center rounded-full bg-[rgba(252,205,3,0.18)] text-app-yellow">
          <GridIcon />
        </div>
        <div className="text-end">
          <h3 className="text-base font-medium text-app-text">{title}</h3>
          <p className="mt-1 text-xs text-app-muted-light">{helper}</p>
        </div>
      </div>
      <div className="mt-7 grid grid-cols-3 gap-3">
        {branchMiniCards.map((item) => (
          <div key={item.label} className="rounded-xl border border-app-line bg-app-card/80 p-3 text-center">
            <div className="mb-2 flex items-center justify-center gap-1 text-xs text-app-muted-light">
              <TrendUpIcon className="size-4" />
              {item.label}
            </div>
            <strong className="text-xs font-medium text-white">{item.value}</strong>
          </div>
        ))}
      </div>
    </article>
  );
}

export default function ReportsPage() {
  return (
    <div className="space-y-6">
      <StatsGrid items={reportStats} />

      <section className="grid gap-5 lg:grid-cols-2">
        {branchStats.map((branch) => (
          <BranchSummaryCard key={branch.title} title={branch.title} helper={branch.helper} />
        ))}
      </section>

      <SectionCard title="مقارنة الفروع - ديسمبر 2025" className="min-h-[320px]">
        <LineChart data={branchComparison} legend={{ yellow: "نادي النساء", green: "نادي الرجال" }} />
      </SectionCard>
    </div>
  );
}
