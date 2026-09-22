import { Link } from 'react-router-dom';
import {
  ArrowRight,
  BriefcaseBusiness,
  CalendarDays,
  CheckCircle2,
  Heart,
  MessageCircle,
  MessagesSquare,
  Rocket,
  Search,
  Sparkles,
  UsersRound,
} from 'lucide-react';
import BrandMark from '../components/BrandMark';
import Button from '../components/Button';
import Avatar from '../components/Avatar';
import Surface from '../components/Surface';

function HeroSection() {
  return (
    <section className="mb-20 sm:mb-28">
      <div className="mx-auto max-w-[760px] text-center">
        <p className="mb-4 text-[10px] font-bold uppercase tracking-[.18em] text-clay">SocialBlog</p>
        <h1 className="font-display text-[42px] font-semibold leading-[1.02] tracking-[-.06em] text-ink sm:text-[60px]">
          Where your professional<br />network actually grows.
        </h1>
        <p className="mx-auto mt-6 max-w-[50ch] text-base leading-7 text-muted sm:text-lg sm:leading-8">
          Follow people whose work you respect. Join focused communities. Find jobs, events, and mentorships that matter. All in one place.
        </p>
        <div className="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
          <Link to="/register">
            <Button className="min-w-[180px]">Create your account <ArrowRight aria-hidden="true" className="h-4 w-4" /></Button>
          </Link>
          <Link to="/welcome">
            <Button variant="outline" className="min-w-[180px]">See how it works</Button>
          </Link>
        </div>
        <div className="mt-8 flex items-center justify-center gap-4 text-xs text-muted">
          <span className="flex items-center gap-1.5"><CheckCircle2 className="h-3.5 w-3.5 text-teal" strokeWidth={2} /> Free to join</span>
          <span className="flex items-center gap-1.5"><CheckCircle2 className="h-3.5 w-3.5 text-teal" strokeWidth={2} /> No credit card</span>
          <span className="flex items-center gap-1.5"><CheckCircle2 className="h-3.5 w-3.5 text-teal" strokeWidth={2} /> Set up in 2 minutes</span>
        </div>
      </div>
    </section>
  );
}

function StatsBar() {
  const stats = [
    { value: '12,400+', label: 'Active members' },
    { value: '340', label: 'Communities' },
    { value: '1,200+', label: 'Open roles' },
    { value: '85', label: 'Events this week' },
  ];

  return (
    <section className="mb-20 sm:mb-28">
      <Surface className="p-6 sm:p-8">
        <div className="grid grid-cols-2 gap-6 sm:grid-cols-4 sm:gap-4">
          {stats.map(({ value, label }) => (
            <div key={label} className="text-center">
              <p className="font-display text-2xl font-semibold tracking-[-.03em] text-ink sm:text-3xl">{value}</p>
              <p className="mt-1 text-xs text-muted">{label}</p>
            </div>
          ))}
        </div>
      </Surface>
    </section>
  );
}

function PostShowcase() {
  return (
    <Surface className="p-5 sm:p-6">
      <div className="mb-5 flex items-center justify-between">
        <p className="text-[10px] font-bold uppercase tracking-[.18em] text-teal">Feed</p>
        <span className="text-[11px] text-muted">Public post</span>
      </div>
      <div className="flex items-start gap-3">
        <Avatar name="Maya Chen" size="lg" tone="clay" />
        <div className="min-w-0">
          <div className="flex flex-wrap items-center gap-x-2 gap-y-1">
            <span className="font-display text-sm font-semibold text-ink">Maya Chen</span>
            <span className="text-[11px] text-muted">· Product design</span>
          </div>
          <h3 className="mt-2 font-display text-base font-semibold leading-snug tracking-[-.03em] text-ink">
            The best feedback comes from people who actually use what you build.
          </h3>
          <p className="mt-2 text-sm leading-6 text-muted">
            After three years of running usability sessions, I&apos;ve learned that the most useful critique rarely comes from the loudest voice in the room.
          </p>
          <div className="mt-4 flex items-center gap-4">
            <span className="flex items-center gap-1.5 text-xs text-muted"><Heart className="h-3.5 w-3.5" strokeWidth={1.8} /> 24</span>
            <span className="flex items-center gap-1.5 text-xs text-muted"><MessageCircle className="h-3.5 w-3.5" strokeWidth={1.8} /> 6 comments</span>
          </div>
        </div>
      </div>
    </Surface>
  );
}

function EventShowcase() {
  return (
    <Surface className="p-5 sm:p-6">
      <div className="mb-5 flex items-center justify-between">
        <p className="text-[10px] font-bold uppercase tracking-[.18em] text-teal">Events</p>
        <span className="text-[11px] text-muted">Open gathering</span>
      </div>
      <div className="flex gap-4">
        <div className="flex h-[68px] w-[68px] shrink-0 flex-col items-center justify-center rounded-xl bg-clay-soft text-[#92543d]">
          <CalendarDays aria-hidden="true" className="mb-1 h-4 w-4" strokeWidth={1.8} />
          <span className="text-[10px] font-bold uppercase tracking-[.12em]">OCT</span>
          <span className="font-display text-xl font-semibold leading-none">14</span>
        </div>
        <div className="min-w-0">
          <h3 className="font-display text-base font-semibold leading-snug tracking-[-.03em] text-ink">Design systems in the open</h3>
          <p className="mt-1 text-xs text-muted">Hosted by Independent Makers · 38 attending</p>
          <p className="mt-2 text-sm leading-6 text-muted">A working session for teams who want to share what&apos;s actually holding their design language together.</p>
        </div>
      </div>
    </Surface>
  );
}

function JobShowcase() {
  return (
    <Surface className="p-5 sm:p-6">
      <div className="mb-5 flex items-center justify-between">
        <p className="text-[10px] font-bold uppercase tracking-[.18em] text-teal">Jobs</p>
        <span className="text-[11px] text-muted">Remote</span>
      </div>
      <div className="flex items-start gap-3">
        <Avatar name="Northwind Studio" size="lg" tone="teal" />
        <div className="min-w-0">
          <p className="text-xs font-semibold text-muted">Northwind Studio</p>
          <h3 className="mt-1 font-display text-base font-semibold leading-snug tracking-[-.03em] text-ink">Senior product designer</h3>
          <p className="mt-2 text-sm leading-6 text-muted">Full-time · Remote · Work on tools that help small teams think together.</p>
          <div className="mt-3 flex items-center gap-3">
            <span className="text-xs font-semibold text-ink">$120k–$155k</span>
            <span className="text-xs text-muted">Apply by Nov 15</span>
          </div>
        </div>
      </div>
    </Surface>
  );
}

function ExamplesSection() {
  return (
    <section className="mb-20 sm:mb-28">
      <div className="mx-auto max-w-[960px]">
        <div className="mb-10 text-center">
          <p className="mb-3 text-[10px] font-bold uppercase tracking-[.18em] text-muted">See it in action</p>
          <h2 className="font-display text-[28px] font-semibold leading-[1.05] tracking-[-.05em] text-ink sm:text-[36px]">Your feed, your events, your opportunities.</h2>
        </div>
        <div className="grid gap-4 lg:grid-cols-3">
          <PostShowcase />
          <EventShowcase />
          <JobShowcase />
        </div>
      </div>
    </section>
  );
}

function HowItWorks() {
  const steps = [
    {
      icon: Search,
      title: 'Discover',
      description: 'Browse posts, communities, jobs, and events that match your interests and your field.',
    },
    {
      icon: UsersRound,
      title: 'Connect',
      description: 'Follow people, join communities, and start conversations that help your work grow.',
    },
    {
      icon: Rocket,
      title: 'Contribute',
      description: 'Share your thinking, apply to roles, attend events, and build your professional reputation.',
    },
  ];

  return (
    <section className="mb-20 sm:mb-28">
      <div className="mx-auto max-w-[760px]">
        <div className="mb-10 text-center">
          <p className="mb-3 text-[10px] font-bold uppercase tracking-[.18em] text-muted">How it works</p>
          <h2 className="font-display text-[28px] font-semibold leading-[1.05] tracking-[-.05em] text-ink sm:text-[36px]">Three steps to a stronger network.</h2>
        </div>
        <div className="grid gap-4 sm:grid-cols-3">
          {steps.map(({ icon: Icon, title, description }, index) => (
            <Surface key={title} className="relative p-6">
              <div className="mb-4 flex h-10 w-10 items-center justify-center rounded-xl bg-teal-soft text-teal">
                <Icon aria-hidden="true" className="h-5 w-5" strokeWidth={1.7} />
              </div>
              <div className="absolute right-4 top-4 font-display text-xs font-bold text-muted/40">0{index + 1}</div>
              <h3 className="font-display text-base font-semibold tracking-[-.02em] text-ink">{title}</h3>
              <p className="mt-2 text-sm leading-6 text-muted">{description}</p>
            </Surface>
          ))}
        </div>
      </div>
    </section>
  );
}

function TestimonialSection() {
  return (
    <section className="mb-20 sm:mb-28">
      <div className="mx-auto max-w-[760px]">
        <Surface className="p-8 sm:p-10">
          <div className="flex items-start gap-4">
            <Avatar name="James Okonkwo" size="lg" tone="olive" />
            <div>
              <blockquote className="font-display text-lg font-medium leading-7 tracking-[-.02em] text-ink sm:text-xl">
                &ldquo;SocialBlog replaced three tools for me. I find mentors through the communities, discover job opportunities I wouldn&apos;t see elsewhere, and the conversations are genuinely useful — not noise.&rdquo;
              </blockquote>
              <div className="mt-4">
                <p className="text-sm font-semibold text-ink">James Okonkwo</p>
                <p className="text-xs text-muted">Product Lead · 2 years on SocialBlog</p>
              </div>
            </div>
          </div>
        </Surface>
      </div>
    </section>
  );
}

function FeaturesSection() {
  const features = [
    { label: 'Curated feed', description: 'See posts from people and communities you actually care about.', icon: Sparkles },
    { label: 'Communities', description: 'Join focused groups around your craft, not the latest trends.', icon: UsersRound },
    { label: 'Opportunities', description: 'Find jobs, events, and mentorships matched to your direction.', icon: BriefcaseBusiness },
    { label: 'Direct messages', description: 'Have meaningful conversations with the people who matter.', icon: MessagesSquare },
  ];

  return (
    <section className="mb-20 sm:mb-28">
      <div className="mx-auto max-w-[960px]">
        <div className="mb-10 text-center">
          <p className="mb-3 text-[10px] font-bold uppercase tracking-[.18em] text-muted">What you get</p>
          <h2 className="font-display text-[28px] font-semibold leading-[1.05] tracking-[-.05em] text-ink sm:text-[36px]">Everything your network needs.</h2>
        </div>
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          {features.map(({ label, description, icon: Icon }) => (
            <Surface key={label} className="p-5">
              <div className="mb-4 flex h-10 w-10 items-center justify-center rounded-xl bg-clay-soft text-[#92543d]">
                <Icon aria-hidden="true" className="h-5 w-5" strokeWidth={1.7} />
              </div>
              <h3 className="font-display text-sm font-semibold tracking-[-.02em] text-ink">{label}</h3>
              <p className="mt-2 text-sm leading-6 text-muted">{description}</p>
            </Surface>
          ))}
        </div>
      </div>
    </section>
  );
}

function CtaSection() {
  return (
    <section className="mb-16">
      <Surface className="p-10 sm:p-14">
        <div className="mx-auto max-w-[560px] text-center">
          <p className="mb-3 text-[10px] font-bold uppercase tracking-[.18em] text-teal">Your network is waiting</p>
          <h2 className="font-display text-[28px] font-semibold leading-[1.1] tracking-[-.04em] text-ink sm:text-[36px]">Start building it today.</h2>
          <p className="mx-auto mt-4 max-w-[42ch] text-sm leading-6 text-muted">Create a free profile in under two minutes. Follow people whose work you respect. Start contributing to conversations that shape your field.</p>
          <div className="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
            <Link to="/register">
              <Button className="min-w-[180px]">Create your account <ArrowRight aria-hidden="true" className="h-4 w-4" /></Button>
            </Link>
            <Link to="/welcome">
              <Button variant="outline" className="min-w-[180px]">Explore first</Button>
            </Link>
          </div>
        </div>
      </Surface>
    </section>
  );
}

function Footer() {
  return (
    <footer className="border-t border-rule">
      <div className="mx-auto max-w-[960px] px-5 py-8 sm:px-8">
        <div className="flex flex-col items-center justify-between gap-4 sm:flex-row">
          <BrandMark compact />
          <div className="flex items-center gap-6 text-xs text-muted">
            <Link className="focus-ring hover:text-ink hover:underline" to="/welcome">About</Link>
            <Link className="focus-ring hover:text-ink hover:underline" to="/welcome">Communities</Link>
            <Link className="focus-ring hover:text-ink hover:underline" to="/welcome">Events</Link>
            <Link className="focus-ring hover:text-ink hover:underline" to="/welcome">Jobs</Link>
          </div>
          <p className="text-xs text-muted">Keep the important conversations close.</p>
        </div>
      </div>
    </footer>
  );
}

export default function WelcomePage() {
  return (
    <div className="mx-auto max-w-[1100px] px-5 pt-10 pb-6 sm:px-8 lg:px-10">
      <HeroSection />
      <StatsBar />
      <ExamplesSection />
      <HowItWorks />
      <TestimonialSection />
      <FeaturesSection />
      <CtaSection />
      <Footer />
    </div>
  );
}
