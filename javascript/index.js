// Get the canvas elements
const canvases = Array.from(document.querySelectorAll('canvas'));

// Sample data
const labels = ['January', 'February', 'March', 'April', 'May', 'June'];
const data = {
  labels: labels,
  datasets: [{
    label: 'Sample Dataset',
    backgroundColor: 'rgba(133, 193, 233, 0.2)',
    borderColor: 'rgba(133, 193, 233, 1)',
    data: [0, 10, 5, 2, 20, 30, 45],
  }]
};

// Options for the charts
const options = {
  responsive: true,
  maintainAspectRatio: false,
};

// Create a chart for each canvas
canvases.forEach(canvas => {
  new Chart(canvas, {
    type: 'line',
    data: data,
    options: options
  });
});