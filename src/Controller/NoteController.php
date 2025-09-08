<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Note;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class NoteController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {}

    public function list(Request $request, array $params): JsonResponse
    {
        $repo = $this->em->getRepository(Note::class);
        $notes = $repo->findAll();
        return new JsonResponse(array_map(static function (Note $n): array {
            return [
                'id' => $n->getId(),
                'title' => $n->getTitle(),
                'content' => $n->getContent(),
            ];
        }, $notes));
    }

    public function create(Request $request, array $params): JsonResponse
    {
        $data = json_decode($request->getContent() ?: '[]', true) ?: [];
        $title = (string)($data['title'] ?? 'Untitled');
        $content = (string)($data['content'] ?? '');

        $note = new Note($title, $content);
        $this->em->persist($note);
        $this->em->flush();

        return new JsonResponse([
            'id' => $note->getId(),
            'title' => $note->getTitle(),
            'content' => $note->getContent(),
        ], 201);
    }
}
