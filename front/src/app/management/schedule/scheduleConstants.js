export const SCHEDULE_DAYS = [
  { key: "sat", apiKey: "Saturday", label: "السبت", dayIndex: 6 },
  { key: "sun", apiKey: "Sunday", label: "الأحد", dayIndex: 0 },
  { key: "mon", apiKey: "Monday", label: "الاثنين", dayIndex: 1 },
  { key: "tue", apiKey: "Tuesday", label: "الثلاثاء", dayIndex: 2 },
  { key: "wed", apiKey: "Wednesday", label: "الأربعاء", dayIndex: 3 },
  { key: "thu", apiKey: "Thursday", label: "الخميس", dayIndex: 4 },
  { key: "fri", apiKey: "Friday", label: "الجمعة", dayIndex: 5 },
];

export const SCHEDULE_DEFAULT_SETTINGS = {
  morningStart: "08:00",
  morningEnd: "16:00",
  eveningStart: "16:00",
  eveningEnd: "23:00",
  slotDuration: 60,
};
