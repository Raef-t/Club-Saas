import PageHeader from "@/components/common/PageHeader";
import Button from "@/components/ui/Button";
import { ChevronRight } from "@/components/icons/Icons";

export default function OfferEditorHeader({ isEdit }) {
  return (
    <PageHeader
      eyebrow="إدارة النادي / العروض الترويجية"
      title={isEdit ? "تعديل العرض الترويجي" : "إنشاء عرض ترويجي جديد"}
      subtitle="حدّد الفعاليات والسعر ومدة الإتاحة، ثم راجع ملخص العرض قبل الحفظ."
      action={
        <Button href="/management/offers" tone="outline" icon={<ChevronRight className="size-4" />}>
          العودة للعروض
        </Button>
      }
    />
  );
}
