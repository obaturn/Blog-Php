import { useNavigate } from 'react-router-dom';
import JobForm from '../components/JobForm';
import PageHeader from '../components/PageHeader';

export default function CreateJobPage() {
  const navigate = useNavigate();

  return (
    <div className="mx-auto max-w-[820px]">
      <PageHeader description="Share the context, constraints, and kind of person who would make this role meaningful." eyebrow="Jobs" title="Post a role." />
      <JobForm onCreated={(job) => navigate(`/jobs/${job.id}`, { replace: true })} />
    </div>
  );
}
