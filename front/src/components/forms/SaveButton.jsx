export function SaveButton({ className = "" }) {
  return (
    <button className={`app-button-primary inline-flex h-[34px] items-center justify-center gap-3 rounded-lg px-8 text-sm font-medium ${className}`} type="button">
      حفظ
      <span className="text-xl leading-none">+</span>
    </button>
  );
}
