import { useNavigate } from 'react-router-dom';
import EventForm from '../components/EventForm';
import PageHeader from '../components/PageHeader';

export default function CreateEventPage() {
  const navigate = useNavigate();

  return (
    <div className="mx-auto max-w-[820px]">
      <PageHeader description="Give people enough context to decide whether this is the right room for them." eyebrow="Events" title="Host a gathering." />
      <EventForm onCreated={(event) => navigate(`/events/${event.id}`, { replace: true })} />
    </div>
  );
}
