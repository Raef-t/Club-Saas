import "./globals.css";
import { ThemeProvider } from "@/components/ThemeProvider";
import StoreProvider from "@/lib/StoreProvider";
import { ToastProvider } from "@/components/ui/Toast";
import { TimeFormatProvider } from "@/lib/TimeFormatContext";
import { GlobalScripts } from "@/components/GlobalScripts";

export const metadata = {
  title: "TechnoGYM | إدارة النادي",
  description: "لوحة إدارة النادي والاشتراكات والتقارير",
  appleWebApp: {
    capable: true,
    statusBarStyle: "black-translucent",
    title: "TechnoGYM",
  },
};


export default function RootLayout({ children }) {
  return (
    <html lang="ar" dir="rtl" suppressHydrationWarning data-scroll-behavior="smooth">
      <body className="antialiased" dir="rtl">
        <ThemeProvider attribute="class" defaultTheme="dark" enableSystem={false}>
          <TimeFormatProvider>
            <StoreProvider>
              <ToastProvider>
                <GlobalScripts />
                {children}
              </ToastProvider>
            </StoreProvider>
          </TimeFormatProvider>
        </ThemeProvider>
      </body>
    </html>
  );
}
