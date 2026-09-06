import React from 'react';
import { createRoot } from 'react-dom/client';
import NexumHero from './components/NexumHero';
import AuroraSignUp from './components/AuroraSignUp';
import ForTutorsPage from './components/ForTutorsPage';

const appContainer = document.getElementById('app');
if (appContainer) {
  const root = createRoot(appContainer);
  root.render(<NexumHero />);
}

const authContainer = document.getElementById('aurora-auth');
if (authContainer) {
  const root = createRoot(authContainer);
  root.render(<AuroraSignUp />);
}

const tutorsContainer = document.getElementById('for-tutors-app');
if (tutorsContainer) {
  const root = createRoot(tutorsContainer);
  root.render(<ForTutorsPage />);
}

