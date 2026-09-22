export function getInitials(name) {
  if (!name) return 'SB';

  const initials = name
    .trim()
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase())
    .join('');

  return initials || 'SB';
}

export function formatRelativeTime(value) {
  if (!value) return 'Recently';

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return 'Recently';

  const difference = Date.now() - date.getTime();
  const minutes = Math.max(0, Math.floor(difference / 60000));

  if (minutes < 1) return 'Just now';
  if (minutes < 60) return `${minutes} min`;

  const hours = Math.floor(minutes / 60);
  if (hours < 24) return `${hours} hr`;

  const days = Math.floor(hours / 24);
  if (days < 7) return `${days}d`;

  return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
}

export function formatCount(value) {
  const count = Number(value || 0);
  if (count < 1000) return String(count);
  if (count < 1000000) return `${(count / 1000).toFixed(count >= 10000 ? 0 : 1)}k`;
  return `${(count / 1000000).toFixed(1)}m`;
}

export function formatDateLabel(value) {
  if (!value) return 'Date to be announced';

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return 'Date to be announced';

  return date.toLocaleDateString(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
}

export function formatEmploymentType(value) {
  if (!value) return 'Opportunity';

  return value
    .split('_')
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(' ');
}

export function formatWorkplaceType(value) {
  const labels = {
    onsite: 'On-site',
    hybrid: 'Hybrid',
    remote: 'Remote',
  };

  return labels[value] || 'Workplace flexible';
}

export function formatEventDateRange(startsAt, endsAt) {
  if (!startsAt) return 'Date to be announced';

  const start = new Date(startsAt);
  const end = endsAt ? new Date(endsAt) : null;
  if (Number.isNaN(start.getTime())) return 'Date to be announced';

  const dateLabel = start.toLocaleDateString(undefined, {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
  });
  const startTime = start.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
  if (!end || Number.isNaN(end.getTime())) return `${dateLabel} · ${startTime}`;

  const endTime = end.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
  const sameDay = start.toDateString() === end.toDateString();
  if (sameDay) return `${dateLabel} · ${startTime}–${endTime}`;

  const endDateLabel = end.toLocaleDateString(undefined, {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
  });
  return `${dateLabel}, ${startTime} → ${endDateLabel}, ${endTime}`;
}

export function formatEventDay(value) {
  if (!value) return { month: 'TBD', day: '—' };

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return { month: 'TBD', day: '—' };

  return {
    month: date.toLocaleDateString(undefined, { month: 'short' }).toUpperCase(),
    day: date.getDate(),
  };
}

export function formatEventCapacity(attendeesCount, maxAttendees) {
  const count = Number(attendeesCount || 0);
  if (maxAttendees === null || maxAttendees === undefined || maxAttendees === '') return `${count} attending`;
  return `${count} of ${maxAttendees} attending`;
}

export function formatSalaryRange(min, max, currency = 'USD') {
  const hasMin = min !== null && min !== undefined && min !== '';
  const hasMax = max !== null && max !== undefined && max !== '';
  if (!hasMin && !hasMax) return 'Compensation not listed';

  let formatter;
  try {
    formatter = new Intl.NumberFormat(undefined, {
      style: 'currency',
      currency: currency || 'USD',
      maximumFractionDigits: 0,
    });
  } catch {
    formatter = new Intl.NumberFormat(undefined, { maximumFractionDigits: 0 });
  }

  if (hasMin && hasMax) return `${formatter.format(Number(min))}–${formatter.format(Number(max))}`;
  if (hasMin) return `From ${formatter.format(Number(min))}`;
  return `Up to ${formatter.format(Number(max))}`;
}
