import { ArrowUpIcon } from "@/components/icons/Icons";
import { FormCard } from "./FormCard";

export function TransactionTypeSelector({ selected = "income" }) {
  return (
    <FormCard title="نوع المعاملة" className="bg-app-card-soft">
      <div className="flex flex-col justify-center gap-5 sm:flex-row">
        <button className={`inline-flex h-[44px] min-w-[176px] items-center justify-center gap-2 rounded-lg border px-6 text-base ${selected === "expense" ? "border-app-red bg-app-red/30 text-white" : "border-app-red/50 bg-app-red/15 text-white/75"}`}>
          <ArrowUpIcon className="size-5" />
          مصروف (صادر)
        </button>
        <button className={`inline-flex h-[44px] min-w-[176px] items-center justify-center gap-2 rounded-lg border px-6 text-base ${selected === "income" ? "border-app-green bg-app-green/30 text-white" : "border-app-green/50 bg-app-green/15 text-white/75"}`}>
          <ArrowUpIcon className="size-5 rotate-180" />
          إيراد (وارد)
        </button>
      </div>
    </FormCard>
  );
}
